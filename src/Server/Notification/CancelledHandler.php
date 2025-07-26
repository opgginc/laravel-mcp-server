<?php

namespace OPGG\LaravelMcpServer\Server\Notification;

use Illuminate\Support\Facades\Log;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;

class CancelledHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'notifications/cancelled';

    public function execute(?array $params = null): void
    {
        // Handle cancellation notifications from clients
        // This is sent when a client wants to cancel a previously sent request

        $requestId = $params['requestId'] ?? null;
        $reason = $params['reason'] ?? 'No reason provided';

        if ($requestId) {
            Log::info('MCP Request Cancelled', [
                'request_id' => $requestId,
                'reason' => $reason,
                'timestamp' => now()->toISOString(),
            ]);

            // You can implement custom logic here, such as:
            // - Stopping ongoing operations for the cancelled request
            // - Cleaning up resources allocated for the request
            // - Notifying other parts of your application about the cancellation
            // - Updating request status in database
        }
    }
}
