<?php

namespace OPGG\LaravelMcpServer\Protocol;

use Exception;
use Illuminate\Validation\ValidationException;
use OPGG\LaravelMcpServer\Data\Requests\NotificationData;
use OPGG\LaravelMcpServer\Data\Requests\RequestData;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcResultResource;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Transports\TransportInterface;
use OPGG\LaravelMcpServer\Utils\DataUtil;

/**
 * MCPProtocol
 *
 * @see https://modelcontextprotocol.io/docs/concepts/architecture
 */
final class MCPProtocol
{
    public const PROTOCOL_VERSION = '2024-11-05';

    private TransportInterface $transport;

    /**
     * @var RequestHandler[]
     */
    private array $requestHandlers = [];

    /**
     * @var NotificationHandler[]
     */
    private array $notificationHandlers = [];

    /**
     * @param  TransportInterface  $transport  The transport implementation to use for communication
     * @return void
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
        $this->transport->onMessage([$this, 'handleMessage']);
    }

    /**
     * @throws Exception
     */
    public function connect(): void
    {
        $this->transport->start();

        while ($this->transport->isConnected()) {
            foreach ($this->transport->receive() as $message) {
                if ($message === null) {
                    continue;
                }

                $this->send(message: $message);
            }

            usleep(10000); // 10ms
        }

        $this->disconnect();
    }

    public function send(string|array $message): void
    {
        $this->transport->send(message: $message);
    }

    public function disconnect(): void
    {
        $this->transport->close();
    }

    public function registerRequestHandler(RequestHandler $handler): void
    {
        $this->requestHandlers[] = $handler;
    }

    public function registerNotificationHandler(NotificationHandler $handler): void
    {
        $this->notificationHandlers[] = $handler;
    }

    public function handleMessage(string $clientId, array $message): void
    {
        $messageId = $message['id'] ?? null;
        try {
            if (! isset($message['jsonrpc']) || $message['jsonrpc'] !== '2.0') {
                throw new JsonRpcErrorException(message: 'Invalid Request: Not a valid JSON-RPC 2.0 message', code: JsonRpcErrorCode::INVALID_REQUEST);
            }

            $requestData = DataUtil::makeRequestData(message: $message);
            if ($requestData instanceof RequestData) {
                $this->handleRequestProcess(clientId: $clientId, requestData: $requestData);

                return;
            }
            if ($requestData instanceof NotificationData) {
                $this->handleNotificationProcess(clientId: $clientId, notificationData: $requestData);

                return;
            }

            throw new JsonRpcErrorException(message: 'Invalid Request: Message format not recognized', code: JsonRpcErrorCode::INVALID_REQUEST);
        } catch (JsonRpcErrorException $e) {
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $e, id: $messageId));
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Handles incoming request messages.
     * Finds a matching request handler and executes it.
     * Sends the result or an error back to the client.
     *
     * @param  string  $clientId  The identifier of the client sending the request.
     * @param  RequestData  $requestData  The parsed request data object.
     */
    private function handleRequestProcess(string $clientId, RequestData $requestData): void
    {
        $messageId = $requestData->id;
        try {
            foreach ($this->requestHandlers as $handler) {
                if ($handler->isHandle(method: $requestData->method)) {
                    $result = $handler->execute(method: $requestData->method, params: $requestData->params);

                    $resultResource = new JsonRpcResultResource(id: $requestData->id, result: $result);
                    $this->pushMessage(clientId: $clientId, message: $resultResource);

                    return;
                }
            }

            throw new JsonRpcErrorException("Method not found: {$requestData->method}", JsonRpcErrorCode::METHOD_NOT_FOUND);
        } catch (JsonRpcErrorException $e) {
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $e, id: $messageId));
        } catch (ValidationException $e) {
            $jsonRpcErrorException = new JsonRpcErrorException(message: $e->getMessage(), code: JsonRpcErrorCode::INVALID_PARAMS);
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $jsonRpcErrorException, id: $messageId));
        } catch (Exception $e) {
            $jsonRpcErrorException = new JsonRpcErrorException(message: $e->getMessage(), code: JsonRpcErrorCode::INTERNAL_ERROR);
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $jsonRpcErrorException, id: $messageId));
        }
    }

    /**
     * Handles incoming notification messages.
     * Finds a matching notification handler and executes it.
     * Does not send a response back to the client for notifications.
     *
     * @param  string  $clientId  The identifier of the client sending the notification.
     * @param  NotificationData  $notificationData  The parsed notification data object.
     */
    private function handleNotificationProcess(string $clientId, NotificationData $notificationData): void
    {
        try {
            foreach ($this->notificationHandlers as $handler) {
                if ($handler->isHandle(method: $notificationData->method)) {
                    $handler->execute(params: $notificationData->params);

                    return;
                }
            }

            throw new JsonRpcErrorException("Method not found: {$notificationData->method}", JsonRpcErrorCode::METHOD_NOT_FOUND);
        } catch (JsonRpcErrorException $e) {
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $e, id: null));
        } catch (Exception $e) {
            $jsonRpcErrorException = new JsonRpcErrorException(message: $e->getMessage(), code: JsonRpcErrorCode::INTERNAL_ERROR);
            $this->pushMessage(clientId: $clientId, message: new JsonRpcErrorResource(exception: $jsonRpcErrorException, id: null));
        }
    }

    /**
     * @throws Exception
     */
    private function pushMessage(string $clientId, array|JsonRpcResultResource|JsonRpcErrorResource $message): void
    {
        if ($message instanceof JsonRpcResultResource || $message instanceof JsonRpcErrorResource) {
            $this->transport->pushMessage(clientId: $clientId, message: $message->toResponse());

            return;
        }

        $this->transport->pushMessage(clientId: $clientId, message: $message);
    }

    public function requestMessage(string $clientId, array $message): void
    {
        $this->transport->processMessage(clientId: $clientId, message: $message);
    }
}
