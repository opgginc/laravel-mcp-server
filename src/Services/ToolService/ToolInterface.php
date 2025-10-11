<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    /**
     * Optional human friendly title exposed alongside the machine readable name.
     *
     * Added for MCP 2025-06-18 so the UI can present descriptive labels.
     * Returning null keeps responses compatible with servers that do not need
     * a separate title field.
     *
     * @see https://modelcontextprotocol.io/specification/2025-06-18/server/tools#tool
     */
    public function title(): ?string;

    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    /**
     * Optional JSON schema describing the structured payload emitted by the
     * tool. The 2025-06-18 revision allows clients to validate structuredContent
     * responses. Tools that do not provide a schema may return an empty array.
     *
     * @see https://modelcontextprotocol.io/specification/2025-06-18/server/tools#tool
     */
    public function outputSchema(): array;

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
}
