<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\Concerns\FormatsTabularToolResponses;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

class TabularChampionsTool implements ToolInterface
{
    use FormatsTabularToolResponses;

    public function name(): string
    {
        return 'tabular-champions';
    }

    public function description(): string
    {
        return 'Returns champion metadata formatted as CSV or Markdown.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'format' => [
                    'type' => 'string',
                    'enum' => ['csv', 'markdown'],
                    'description' => 'Output format for the tool response.',
                    'default' => 'csv',
                ],
            ],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): ToolResponse
    {
        $rows = [
            [
                'champion_id' => '1',
                'key' => 'Annie',
                'name' => 'Annie',
            ],
            [
                'champion_id' => '2',
                'key' => 'Olaf',
                'name' => 'Olaf',
            ],
        ];

        $format = $arguments['format'] ?? 'csv';

        return match ($format) {
            'markdown' => $this->toolMarkdownTableResponse($rows),
            default => $this->toolCsvResponse($rows),
        };
    }
}
