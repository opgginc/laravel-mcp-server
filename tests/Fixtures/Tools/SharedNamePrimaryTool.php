<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class SharedNamePrimaryTool implements ToolInterface
{
    public function name(): string
    {
        return 'shared-name-tool';
    }

    public function description(): string
    {
        return 'Primary tool for shared-name resolution tests.';
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
            'source' => 'primary',
        ];
    }
}
