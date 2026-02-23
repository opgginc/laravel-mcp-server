<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class ConstructionCounterTool implements ToolInterface
{
    public static int $constructionCount = 0;

    public function __construct()
    {
        self::$constructionCount++;
    }

    public static function resetCounter(): void
    {
        self::$constructionCount = 0;
    }

    public function name(): string
    {
        return 'construction-counter-tool';
    }

    public function description(): string
    {
        return 'Counts tool constructions for performance regression tests.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'ok',
                ],
            ],
        ];
    }
}
