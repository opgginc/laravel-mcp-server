<?php

namespace OPGG\LaravelMcpServer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \OPGG\LaravelMcpServer\LaravelMcpServer
 *
 * @method static void sseRoute(array $middleware = [])
 */
class LaravelMcpServer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \OPGG\LaravelMcpServer\LaravelMcpServer::class;
    }
}
