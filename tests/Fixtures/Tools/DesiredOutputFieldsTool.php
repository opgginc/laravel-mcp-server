<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class DesiredOutputFieldsTool implements ToolInterface
{
    public function name(): string
    {
        return 'desired-output-fields-tool';
    }

    public function description(): string
    {
        return 'Fixture tool for validating desired_output_fields array query parameter export.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'champion' => [
                    'type' => 'string',
                    'description' => 'Champion name in UPPER_SNAKE_CASE (e.g., AHRI, LEE_SIN)',
                ],
                'desired_output_fields' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'string',
                        'enum' => ['runes', 'items', 'counters'],
                    ],
                    'description' => 'Output fields to extract.',
                ],
            ],
            'required' => ['champion', 'desired_output_fields'],
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
