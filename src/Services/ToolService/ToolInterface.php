<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    public function name(): string;

    public function description(): string;

    /**
     * Return either:
     * - A full JSON Schema root object array.
     * - A property map (Laravel-style) where each value is an OPGG JsonSchema Type.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    public function annotations(): array;

    public function execute(array $arguments): mixed;

    /*
     * Optional helpers recognised by ToolRepository when present:
     * - title(): string|null                -> Human readable tool title per MCP schema.
     * - icons(): array                      -> Optional icon metadata entries.
     * - outputSchema(): array               -> JSON schema describing structuredContent responses.
     * - execution(): array                  -> Execution hints advertised in tools/list.
     * - meta()/_meta(): array               -> Optional metadata emitted under `_meta`.
     */
}
