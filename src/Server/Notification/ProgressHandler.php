<?php

namespace OPGG\LaravelMcpServer\Server\Notification;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;

class ProgressHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'notifications/progress';

    public function execute(?array $params = null): void
    {
        // Handle progress notifications from clients
        // These are typically sent during long-running operations
        // to inform the server about progress updates

        $progressToken = $params['progressToken'] ?? null;
        $progress = $params['progress'] ?? null;
        $total = $params['total'] ?? null;

        // Log progress update for debugging/monitoring
        if ($progressToken && $progress !== null) {
            \Log::info('MCP Progress Update', [
                'token' => $progressToken,
                'progress' => $progress,
                'total' => $total,
                'percentage' => $total ? round(($progress / $total) * 100, 2) : null,
            ]);
        }

        // You can implement custom logic here, such as:
        // - Storing progress in cache/database
        // - Triggering events for real-time updates
        // - Updating progress bars in admin interfaces
    }
}
