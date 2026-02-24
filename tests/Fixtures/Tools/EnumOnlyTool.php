<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class EnumOnlyTool implements ToolInterface
{
    public function name(): string
    {
        return 'enum-only-tool';
    }

    public function description(): string
    {
        return 'Tool with enum-only input to verify Swagger dropdown defaults.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'mode' => [
                    'enum' => ['fast', 'safe'],
                    'description' => 'Execution mode',
                ],
            ],
            'required' => ['mode'],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'mode' => $arguments['mode'] ?? null,
        ];
    }
}
