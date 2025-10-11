<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function annotations(): array;

    public function execute(array $arguments): mixed;

    /**
     * OPTIONAL: Determines if this tool requires streaming (SSE) instead of standard HTTP.
     * Most tools should return false (use HTTP for better performance and compatibility).
     * Only return true if you specifically need real-time streaming capabilities.
     *
     * If not implemented, defaults to false (HTTP transport).
     *
     * @since v1.3.0
     *
     * @return bool
     */
    // public function isStreaming(): bool;

    /*
     * Optional helpers recognised by ToolRepository when present:
     * - title(): string|null                -> Human readable tool title per MCP 2025-06-18.
     * - outputSchema(): array               -> JSON schema describing structuredContent responses.
     */
}
