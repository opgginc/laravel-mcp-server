<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class TabularTool implements ToolInterface
{
    public function name(): string
    {
        return 'tabular-tool';
    }

    public function description(): string
    {
        return 'Returns a flat list of champions for tabular output testing.';
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
            [
                'champion_id' => 266,
                'champion_key' => 'Aatrox',
                'champion_name' => 'Aatrox',
                'release_date' => '2012-12-19',
            ],
            [
                'champion_id' => 103,
                'champion_key' => 'Ahri',
                'champion_name' => 'Ahri',
                'release_date' => '2011-12-14',
            ],
        ];
    }
}
