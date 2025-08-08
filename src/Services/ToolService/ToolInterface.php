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

    /**
     * OPTIONAL: Defines the schema for the tool's output data structure.
     * When implemented, tool responses will be validated against this schema
     * and returned as structured content for better MCP client compatibility.
     *
     * If not implemented, tool responses default to simple text content.
     *
     * @since v1.4.0
     *
     * @return array|null JSON schema for the tool's output, or null if no schema validation needed
     */
    // public function outputSchema(): ?array;
}
