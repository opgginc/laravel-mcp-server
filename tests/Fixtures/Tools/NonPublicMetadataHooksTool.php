<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class NonPublicMetadataHooksTool implements ToolInterface
{
    public function name(): string
    {
        return 'non-public-metadata-hooks-tool';
    }

    public function description(): string
    {
        return 'Defines optional metadata hooks as non-public methods.';
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
            'ok' => true,
            'echo' => $arguments,
        ];
    }

    protected function title(): string
    {
        return 'Should not be called';
    }

    protected function icons(): array
    {
        return [
            [
                'src' => 'https://example.com/icons/non-public.png',
            ],
        ];
    }

    protected function outputSchema(): array
    {
        return [
            'type' => 'object',
        ];
    }

    protected function execution(): array
    {
        return [
            'mode' => 'sync',
        ];
    }

    protected function meta(): array
    {
        return [
            'vendor' => 'hidden',
        ];
    }

    protected function _meta(): array
    {
        return [
            'fallback' => true,
        ];
    }
}
