<?php

namespace OPGG\LaravelMcpServer;

use OPGG\LaravelMcpServer\Routing\McpRouteBuilder;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;

class LaravelMcpServer
{
    public function mcp(string $path = '/'): McpRouteBuilder
    {
        /** @var object $router */
        $router = app('router');

        return app(McpRouteRegistrar::class)->register($router, $path);
    }
}
