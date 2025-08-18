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
        // Check if custom handler is configured
        $customHandler = config('mcp-server.get_handler');
        if ($customHandler && class_exists($customHandler)) {
            $handler = app($customHandler);
            if (method_exists($handler, 'handle')) {
                return $handler->handle($request);
            }
        }

        // Return default info page
        $serverInfo = config('mcp-server.server', ['name' => 'MCP Server', 'version' => '1.0.0']);
        $response = [
            'mcp_version' => '0.1.0',
            'server_info' => $serverInfo,
            'protocol' => 'streamable_http',
        ];

        // Check Accept header to determine response format
        if ($request->accepts('text/html')) {
            $html = $this->generateInfoHtml($serverInfo);
            return response($html, 200)->header('Content-Type', 'text/html');
        }

        return response()->json($response, 200);
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

    /**
     * Generate simple HTML info page
     */
    private function generateInfoHtml(array $serverInfo): string
    {
        $name = htmlspecialchars($serverInfo['name'] ?? 'MCP Server');
        $version = htmlspecialchars($serverInfo['version'] ?? '1.0.0');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            color: #333;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info p {
            margin: 5px 0;
        }
        .info strong {
            color: #2c3e50;
        }
        .footer {
            margin-top: 40px;
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <h1>{$name}</h1>
    <div class="info">
        <p><strong>Version:</strong> {$version}</p>
        <p><strong>Protocol:</strong> Model Context Protocol</p>
        <p><strong>Transport:</strong> Streamable HTTP</p>
    </div>
    <div class="footer">
        <p>This is an MCP (Model Context Protocol) server endpoint.</p>
        <p>Use an MCP-compatible client to interact with this server.</p>
    </div>
</body>
</html>
HTML;
    }
}
