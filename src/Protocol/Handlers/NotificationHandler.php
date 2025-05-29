<?php

namespace OPGG\LaravelMcpServer\Protocol\Handlers;

use Exception;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use stdClass;

abstract class NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = null;

    public function __construct()
    {
        if (static::HANDLE_METHOD === null) {
            throw new Exception('HANDLE_METHOD constant must be defined.');
        }
        if (static::MESSAGE_TYPE === null) {
            throw new Exception('MESSAGE_TYPE constant must be defined.');
        }
    }

    abstract public function execute(?array $params = null): array|stdClass;

    public function getMessageType(?array $params = null): ProcessMessageType
    {
        return static::MESSAGE_TYPE;
    }

    public function getHandleMethod(): string|array
    {
        return static::HANDLE_METHOD;
    }
}
