<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class UnionTypeTool implements ToolInterface
{
    public function name(): string
    {
        return 'union-type-tool';
    }

    public function description(): string
    {
        return 'Tool whose properties have union types, e.g. a nullable integer.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'results_size' => [
                    'type' => ['integer', 'null'],
                    'description' => 'The number of results to return.',
                ],
                'tags' => [
                    'type' => 'array',
                    'items' => ['type' => ['string', 'null']],
                ],
            ],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return ['results_size' => $arguments['results_size'] ?? null];
    }
}
