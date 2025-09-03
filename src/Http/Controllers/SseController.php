<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use OPGG\LaravelMcpServer\Server\MCPServer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController
{
    public function handle(Request $request)
    {
        $server = app(MCPServer::class);

        set_time_limit(0);

        return new StreamedResponse(fn () => $server->connect(), headers: [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
