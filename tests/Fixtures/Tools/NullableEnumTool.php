<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class NullableEnumTool implements ToolInterface
{
    public function name(): string
    {
        return 'nullable-enum-tool';
    }

    public function description(): string
    {
        return 'Tool with nullable enum input for docs default/example behavior.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'region' => [
                    'enum' => [null, 'kr', 'na'],
                    'description' => 'Optional region',
                ],
            ],
            'required' => ['region'],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'region' => $arguments['region'] ?? null,
        ];
    }
}
