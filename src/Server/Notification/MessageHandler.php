<?php

namespace OPGG\LaravelMcpServer\Server\Notification;

use Illuminate\Support\Facades\Log;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;

class MessageHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'notifications/message';

    public function execute(?array $params = null): void
    {
        // Handle general message notifications from clients
        // These can be used for logging, alerts, or general communication

        $level = $params['level'] ?? 'info'; // info, warning, error, debug
        $logger = $params['logger'] ?? 'mcp-client';
        $data = $params['data'] ?? [];

        // Log the message with appropriate level
        match ($level) {
            'error' => Log::error("MCP Client Message [{$logger}]", $data),
            'warning' => Log::warning("MCP Client Message [{$logger}]", $data),
            'debug' => Log::debug("MCP Client Message [{$logger}]", $data),
            default => Log::info("MCP Client Message [{$logger}]", $data),
        };

        // You can implement custom logic here, such as:
        // - Forwarding messages to external logging systems
        // - Triggering alerts based on message level
        // - Storing messages in database for analysis
        // - Broadcasting messages to monitoring dashboards
    }
}
