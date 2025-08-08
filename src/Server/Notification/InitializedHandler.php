<?php

namespace OPGG\LaravelMcpServer\Server\Notification;

use Illuminate\Support\Facades\Log;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;

class InitializedHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'notifications/initialized';

    public function execute(?array $params = null): void
    {
        // The 'notifications/initialized' is sent by the client after the initialization
        // handshake is complete. This is purely for acknowledgment - no response is needed.

        // You can implement custom logic here, such as:
        // - Logging client initialization
        // - Setting up client-specific resources
        // - Triggering initialization events
        // - Recording session start times

        Log::debug('MCP Client Initialized', [
            'params' => $params,
            'initialized_at' => now()->toISOString(),
        ]);
    }
}
