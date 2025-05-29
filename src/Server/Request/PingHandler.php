<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use stdClass;

class PingHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;
    protected const HANDLE_METHOD = 'ping';

    public function execute(string $method, ?array $params = null): stdClass
    {
        return new stdClass;
    }
}
