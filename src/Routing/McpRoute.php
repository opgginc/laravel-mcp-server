<?php

namespace OPGG\LaravelMcpServer\Routing;

final class McpRoute
{
    public static function register(string $path = '/'): McpRouteBuilder
    {
        /** @var object $router */
        $router = app('router');

        return app(McpRouteRegistrar::class)->register($router, $path);
    }
}
