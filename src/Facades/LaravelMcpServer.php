<?php

namespace OPGG\LaravelMcpServer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \OPGG\LaravelMcpServer\Server\MCPServer
 *
 * @method static void sseRoute(array $middleware = [])
 */
class LaravelMcpServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \OPGG\LaravelMcpServer\Server\MCPServer::class;
    }
}
