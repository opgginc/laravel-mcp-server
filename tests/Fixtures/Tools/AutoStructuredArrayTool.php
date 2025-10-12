<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class AutoStructuredArrayTool implements ToolInterface
{
    /**
     * Opt into automatic structuredContent detection for array payloads.
     *
     * @var bool
     */
    protected bool $autoStructuredOutput = true;

    public function name(): string
    {
        return 'auto-structured-array-tool';
    }

    public function description(): string
    {
        return 'Returns an array that should be emitted via structuredContent.';
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
            'echo' => $arguments,
        ];
    }
}
