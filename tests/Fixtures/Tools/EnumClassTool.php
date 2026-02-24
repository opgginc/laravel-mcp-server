<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class EnumClassTool implements ToolInterface
{
    public function name(): string
    {
        return 'enum-class-tool';
    }

    public function description(): string
    {
        return 'Tool using enum class references inside input schema.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'platform' => [
                    'type' => 'string',
                    'enum' => Platform::class,
                    'description' => 'Execution platform',
                ],
            ],
            'required' => ['platform'],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'platform' => $arguments['platform'] ?? null,
        ];
    }
}
