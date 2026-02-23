<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MethodStructuredArrayTool implements ToolInterface
{
    public function autoStructuredOutput(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'method-structured-array-tool';
    }

    public function description(): string
    {
        return 'Returns array payloads and opts into structured output via method.';
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
            'source' => 'method',
            'echo' => $arguments,
        ];
    }
}
