<?php

namespace OPGG\LaravelMcpServer;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Http\Controllers\MessageController;
use OPGG\LaravelMcpServer\Http\Controllers\SseController;
use OPGG\LaravelMcpServer\Server\MCPServer;
use RuntimeException;

/**
 * Laravel Model Context Protocol Server
 *
 * @see https://modelcontextprotocol.io/docs/concepts/architecture
 */
class LaravelMcpServer
{
    /**
     * Register the SSE route for the MCP Server
     *
     * @param  array  $middleware  Additional middleware to apply to the route
     */
    public function sseRoute(array $middleware = []): void
    {
        if (! app()->has(MCPServer::class)) {
            throw new RuntimeException('The MCPServer instance is not registered in the service container. Make sure the "server_provider" config is set to "sse".');
        }

        $path = Config::get('mcp-server.default_path');

        Route::get("{$path}/sse", [SseController::class, 'handle'])
            ->middleware($middleware);

        Route::post("{$path}/message", [MessageController::class, 'handle']);
    }
}
