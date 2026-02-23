<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class NonPublicMethodStructuredArrayTool implements ToolInterface
{
    public function name(): string
    {
        return 'non-public-method-structured-array-tool';
    }

    public function description(): string
    {
        return 'Returns array payloads with a non-public autoStructuredOutput helper.';
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
            'status' => 'ok',
            'source' => 'non-public-method',
            'echo' => $arguments,
        ];
    }

    protected function autoStructuredOutput(): bool
    {
        return true;
    }
}
