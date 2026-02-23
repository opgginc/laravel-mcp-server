<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

class StructuredOnlyTool implements ToolInterface
{
    public function name(): string
    {
        return 'structured-only-tool';
    }

    public function description(): string
    {
        return 'Returns only structured content through ToolResponse::structured.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'region' => [
                    'type' => 'string',
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
        return ToolResponse::structured([
            'status' => 'ok',
            'region' => $arguments['region'] ?? 'KR',
        ]);
    }
}
