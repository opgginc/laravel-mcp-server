<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use OPGG\LaravelMcpServer\Server\MCPServer;

class MessageController
{
    public function handle(Request $request)
    {
        $sessionId = $request->input('sessionId');

        $messageJson = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $server = app(MCPServer::class);
        $server->requestMessage(clientId: $sessionId, message: $messageJson);

        return response()->json(['success' => true]);
    }
}
