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
     * OPTIONAL: Defines the JSON schema for the tool's output structure.
     * This enables structured content responses and validation according to MCP specification.
     *
     * When implemented, tool outputs will be validated against this schema and can include
     * structured content with proper type information for better MCP client compatibility.
     *
     * If not implemented, tool outputs default to plain text content.
     *
     * @since v1.4.0
     *
     * @return array|null JSON schema array or null if not supported
     */
    // public function outputSchema(): ?array;
}
