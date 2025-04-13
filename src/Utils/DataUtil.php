<?php

namespace OPGG\LaravelMcpServer\Utils;

use OPGG\LaravelMcpServer\Data\Requests\NotificationData;
use OPGG\LaravelMcpServer\Data\Requests\RequestData;

class DataUtil
{
    public static function makeRequestData(array $message): RequestData|NotificationData|null
    {
        if (isset($message['method'])) {
            if (isset($message['id'])) {
                return RequestData::fromArray(data: $message);
            } else {
                return NotificationData::fromArray(data: $message);
            }
        }

        return null;
    }
}
