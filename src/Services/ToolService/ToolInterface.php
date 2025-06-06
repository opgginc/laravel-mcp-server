<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType;

    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function annotations(): array;

    public function execute(array $arguments): mixed;
}
