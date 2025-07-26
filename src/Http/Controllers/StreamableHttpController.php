<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcResultResource;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Server\MCPServer;

class StreamableHttpController
{
    public function getHandle(Request $request)
    {
        $server = app(MCPServer::class);
        set_time_limit(0);

        $mcpSessionId = $request->headers->get('mcp-session-id');
        $lastEventId = $request->headers->get('last-event-id');

        // todo:: SSE connection configuration restricted

        return response()->json([
            'jsonrpc' => '2.0',
            'error' => 'Method Not Allowed',
        ], 405);
    }

    public function postHandle(Request $request)
    {
        $server = app(MCPServer::class);

        $mcpSessionId = $request->headers->get('mcp-session-id');
        if (! $mcpSessionId) {
            $mcpSessionId = Str::uuid()->toString();
        }

        $messageJson = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $processMessageData = $server->requestMessage(clientId: $mcpSessionId, message: $messageJson);

        // MCP specification: notifications should return HTTP 202 with no body
        if ($processMessageData->isNotification) {
            return response('', 202);
        }

        if (in_array($processMessageData->messageType, [ProcessMessageType::HTTP])
            && ($processMessageData->resource instanceof JsonRpcResultResource || $processMessageData->resource instanceof JsonRpcErrorResource)) {
            return response()->json($processMessageData->resource->toResponse());
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'error' => 'Bad Request: invalid session ID or method.',
        ], 400);
    }
}
