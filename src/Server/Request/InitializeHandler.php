<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Data\Requests\InitializeData;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Server\MCPServer;

class InitializeHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;

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
        $data = InitializeData::fromArray(data: $params);
        $result = $this->server->initialize(data: $data);

        return $result->toArray();
    }
}
