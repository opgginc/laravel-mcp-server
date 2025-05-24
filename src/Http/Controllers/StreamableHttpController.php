<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use OPGG\LaravelMcpServer\Server\MCPServer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller handling the Streamable HTTP transport endpoint.
 */
final class StreamableHttpController extends Controller
{
    public function handle(Request $request)
    {
        $server = app(MCPServer::class);

        if ($request->isMethod('get')) {
            return new StreamedResponse(
                fn () => $server->connect(),
                headers: [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no',
                ]
            );
        }

        if ($request->isMethod('post')) {
            $sessionId = $request->header('Mcp-Session-Id');
            $messageJson = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

            $server->requestMessage(clientId: (string) $sessionId, message: $messageJson);

            return Response::json(['accepted' => true], 202);
        }

        return Response::json(['error' => 'Method Not Allowed'], 405);
    }
}
