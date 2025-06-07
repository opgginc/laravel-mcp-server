<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    /**
     * Determines if this tool requires streaming (SSE) instead of standard HTTP.
     * Most tools should return false (use HTTP for better performance and compatibility).
     * Only return true if you specifically need real-time streaming capabilities.
     *
     * @since v1.3.0
     */
    public function isStreaming(): bool;

    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function annotations(): array;

    public function execute(array $arguments): mixed;
}
