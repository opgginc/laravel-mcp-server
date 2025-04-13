<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Data\Requests\InitializeData;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Server\MCPServer;

class InitializeHandler implements RequestHandler
{
    private MCPServer $server;

    public function __construct(MCPServer $server)
    {
        $this->server = $server;
    }

    public function isHandle(string $method): bool
    {
        return $method === 'initialize';
    }

    /**
     * @throws JsonRpcErrorException
     */
    public function execute(string $method, ?array $params = null): array
    {
        $data = InitializeData::fromArray(data: $params);
        $result = $this->server->initialize(data: $data);

        return $result->toArray();
    }
}
