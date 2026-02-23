<?php

namespace OPGG\LaravelMcpServer\Data;

use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcResultResource;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

final class ProcessMessageData
{
    public ProcessMessageType $messageType;

    public array|JsonRpcResultResource|JsonRpcErrorResource $resource;

    public bool $isNotification;

    public function __construct(ProcessMessageType $messageType, array|JsonRpcResultResource|JsonRpcErrorResource $resource, bool $isNotification = false)
    {
        $this->messageType = $messageType;
        $this->resource = $resource;
        $this->isNotification = $isNotification;
    }

    public function toArray(): array
    {
        if ($this->resource instanceof JsonRpcResultResource || $this->resource instanceof JsonRpcErrorResource) {
            return $this->resource->toResponse();
        }

        return $this->resource;
    }
}
