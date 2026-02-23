<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MetadataAwareTool implements ToolInterface
{
    public function name(): string
    {
        return 'metadata-aware-tool';
    }

    public function description(): string
    {
        return 'Exposes schema metadata fields for tools/list payload validation.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function annotations(): array
    {
        return [
            'title' => 'Metadata Aware Tool',
        ];
    }

    public function meta(): array
    {
        return [
            'vendor' => 'opgg',
        ];
    }

    public function execution(): array
    {
        return [
            'mode' => 'sync',
        ];
    }

    public function execute(array $arguments): array
    {
        return [
            'ok' => true,
        ];
    }
}
