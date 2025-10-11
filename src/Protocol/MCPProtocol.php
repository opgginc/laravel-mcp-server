<?php

namespace OPGG\LaravelMcpServer\Protocol;

use Exception;
use Illuminate\Support\Facades\Log;
use OPGG\LaravelMcpServer\Data\ProcessMessageData;
use OPGG\LaravelMcpServer\Data\Requests\NotificationData;
use OPGG\LaravelMcpServer\Data\Requests\RequestData;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcResultResource;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
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
    /**
     * Protocol version advertised by the server.
     *
     * The revision date reflects the upstream MCP specification that introduced
     * structured tool results and paginated tool discovery.
     * Keeping this in sync with the spec ensures clients can safely enable the
     * newly required behaviour introduced in the 2025-06-18 revision.
     *
     * @see https://modelcontextprotocol.io/specification/2025-06-18/basic/index
     */
    public const PROTOCOL_VERSION = '2025-06-18';

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
        if (is_string($handler->getHandleMethod())) {
            $this->requestHandlers[$handler->getHandleMethod()] = $handler;
        }
        if (is_array($handler->getHandleMethod())) {
            foreach ($handler->getHandleMethod() as $method) {
                $this->requestHandlers[$method] = $handler;
            }
        }
    }

    public function registerNotificationHandler(NotificationHandler $handler): void
    {
        if (is_string($handler->getHandleMethod())) {
            $this->notificationHandlers[$handler->getHandleMethod()] = $handler;
        }
        if (is_array($handler->getHandleMethod())) {
            foreach ($handler->getHandleMethod() as $method) {
                $this->notificationHandlers[$method] = $handler;
            }
        }
    }

    /**
     * @throws JsonRpcErrorException
     * @throws Exception
     */
    public function handleMessage(string $clientId, array $message): ProcessMessageData
    {
        $messageId = $message['id'] ?? null;
        try {
            if (! isset($message['jsonrpc']) || $message['jsonrpc'] !== '2.0') {
                throw new JsonRpcErrorException(message: 'Invalid Request: Not a valid JSON-RPC 2.0 message', code: JsonRpcErrorCode::INVALID_REQUEST);
            }

            $requestData = DataUtil::makeRequestData(message: $message);
            if ($requestData instanceof RequestData) {
                return $this->processRequestData(clientId: $clientId, requestData: $requestData);
            }
            if ($requestData instanceof NotificationData) {
                return $this->processNotification(clientId: $clientId, notificationData: $requestData);
            }

            throw new JsonRpcErrorException(message: 'Invalid Request: Message format not recognized', code: JsonRpcErrorCode::INVALID_REQUEST);
        } catch (JsonRpcErrorException $e) {
            $jsonErrorResource = new JsonRpcErrorResource(exception: $e, id: $messageId);
            $this->sendSSEMessage(clientId: $clientId, message: $jsonErrorResource);

            return new ProcessMessageData(messageType: ProcessMessageType::HTTP, resource: $jsonErrorResource, isNotification: false);
        } catch (Exception $e) {
            $jsonErrorResource = new JsonRpcErrorResource(
                exception: new JsonRpcErrorException(message: 'INTERNAL_ERROR', code: JsonRpcErrorCode::INTERNAL_ERROR),
                id: $messageId
            );
            $this->sendSSEMessage(clientId: $clientId, message: $jsonErrorResource);

            return new ProcessMessageData(messageType: ProcessMessageType::HTTP, resource: $jsonErrorResource, isNotification: false);
        }
    }

    /**
     * Handles incoming request messages.
     * Finds a matching request handler and executes it.
     * Sends the result or an error back to the client.
     *
     * @param  string  $clientId  The identifier of the client sending the request.
     * @param  RequestData  $requestData  The parsed request data object.
     *
     * @throws Exception
     */
    private function processRequestData(string $clientId, RequestData $requestData): ProcessMessageData
    {
        $method = $requestData->method;
        $handler = $this->requestHandlers[$method] ?? null;
        if ($handler) {
            $result = $handler->execute(method: $requestData->method, params: $requestData->params);
            $messageType = $handler->getMessageType($requestData->params);

            $resultResource = new JsonRpcResultResource(id: $requestData->id, result: $result);
            $processMessageData = new ProcessMessageData(messageType: $messageType, resource: $resultResource, isNotification: false);

            if ($processMessageData->isSSEMessage()) {
                $this->sendSSEMessage(clientId: $clientId, message: $resultResource);
            }

            return $processMessageData;
        }

        throw new JsonRpcErrorException("Method not found: {$requestData->method}", JsonRpcErrorCode::METHOD_NOT_FOUND);
    }

    /**
     * @throws Exception
     */
    private function sendSSEMessage(string $clientId, array|JsonRpcResultResource|JsonRpcErrorResource $message): void
    {
        if ($message instanceof JsonRpcResultResource || $message instanceof JsonRpcErrorResource) {
            $this->transport->pushMessage(clientId: $clientId, message: $message->toResponse());

            return;
        }

        $this->transport->pushMessage(clientId: $clientId, message: $message);
    }

    /**
     * Handles incoming notification messages.
     * Finds a matching notification handler and executes it.
     * Does not send a response back to the client for notifications.
     *
     * @param  string  $clientId  The identifier of the client sending the notification.
     * @param  NotificationData  $notificationData  The parsed notification data object.
     */
    private function processNotification(string $clientId, NotificationData $notificationData): ProcessMessageData
    {
        $method = $notificationData->method;
        $handler = $this->notificationHandlers[$method] ?? null;

        if ($handler) {
            try {
                $handler->execute(params: $notificationData->params);
                $messageType = $handler->getMessageType($notificationData->params);

                // Notifications don't return data to client, create empty result
                $resultResource = new JsonRpcResultResource(id: null, result: []);
                $processMessageData = new ProcessMessageData(messageType: $messageType, resource: $resultResource, isNotification: true);

                if ($processMessageData->isSSEMessage()) {
                    $this->sendSSEMessage(clientId: $clientId, message: $resultResource);
                }

                return $processMessageData;
            } catch (Exception $e) {
                // Log notification execution errors but don't send error response to client
                // per JSON-RPC specification for notifications
                Log::error('MCP Notification Handler Error', [
                    'method' => $method,
                    'client_id' => $clientId,
                    'params' => $notificationData->params,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return empty success response for notifications even if handler fails
                $resultResource = new JsonRpcResultResource(id: null, result: []);

                return new ProcessMessageData(messageType: ProcessMessageType::HTTP, resource: $resultResource, isNotification: true);
            }
        }

        // Log unknown notification methods
        Log::warning('MCP Unknown Notification Method', [
            'method' => $method,
            'client_id' => $clientId,
            'params' => $notificationData->params,
        ]);

        // For notifications, we should not throw errors to client, just log and return success
        $resultResource = new JsonRpcResultResource(id: null, result: []);

        return new ProcessMessageData(messageType: ProcessMessageType::HTTP, resource: $resultResource, isNotification: true);
    }
}
