<?php

namespace OPGG\LaravelMcpServer\Server\Notification;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;
use stdClass;

class InitializedHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'notifications/initialized';

    public function execute(?array $params = null): array|stdClass
    {
        // The 'notifications/initialized' is sent by the client after the initialization
        // handshake is complete. This is purely for acknowledgment - no response is needed.
        // We return an empty array to indicate successful processing.
        return [];
    }
}