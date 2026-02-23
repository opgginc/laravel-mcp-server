<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Data\Requests\InitializeData;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Server\MCPServer;

class InitializeHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'initialize';

    private MCPServer $server;

    public function __construct(MCPServer $server)
    {
        parent::__construct();
        $this->server = $server;
    }

    /**
     * @throws JsonRpcErrorException
     */
    public function execute(string $method, ?array $params = null): array
    {
        $this->validateParams($params);

        $data = InitializeData::fromArray(data: $params);
        $result = $this->server->initialize(data: $data);

        return $result->toArray();
    }

    /**
     * @param  array<string, mixed>|null  $params
     *
     * @throws JsonRpcErrorException
     */
    private function validateParams(?array $params): void
    {
        if (! is_array($params)) {
            throw new JsonRpcErrorException(message: 'initialize params are required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        $protocolVersion = $params['protocolVersion'] ?? $params['version'] ?? null;
        if (! is_string($protocolVersion) || trim($protocolVersion) === '') {
            throw new JsonRpcErrorException(message: 'initialize params.protocolVersion is required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        if (! array_key_exists('capabilities', $params) || ! is_array($params['capabilities'])) {
            throw new JsonRpcErrorException(message: 'initialize params.capabilities is required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        if (! array_key_exists('clientInfo', $params) || ! is_array($params['clientInfo'])) {
            throw new JsonRpcErrorException(message: 'initialize params.clientInfo is required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        $clientInfo = $params['clientInfo'];
        $clientName = $clientInfo['name'] ?? null;
        if (! is_string($clientName) || trim($clientName) === '') {
            throw new JsonRpcErrorException(message: 'initialize params.clientInfo.name is required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        $clientVersion = $clientInfo['version'] ?? null;
        if (! is_string($clientVersion) || trim($clientVersion) === '') {
            throw new JsonRpcErrorException(message: 'initialize params.clientInfo.version is required.', code: JsonRpcErrorCode::INVALID_PARAMS);
        }
    }
}
