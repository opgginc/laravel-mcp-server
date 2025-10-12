<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class LegacyArrayTool implements ToolInterface
{
    public function name(): string
    {
        return 'legacy-array-tool';
    }

    public function description(): string
    {
        return 'Returns a simple associative array for backward compatibility tests.';
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
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'status' => 'ok',
            'echo' => $arguments,
        ];
    }
}
