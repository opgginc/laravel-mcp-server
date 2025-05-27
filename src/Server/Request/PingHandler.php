<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use stdClass;

class PingHandler implements RequestHandler
{
    public function isHandle(string $method): bool
    {
        return $method === 'ping';
    }

    public function execute(string $method, ?array $params = null): stdClass
    {
        return new stdClass;
    }
}
