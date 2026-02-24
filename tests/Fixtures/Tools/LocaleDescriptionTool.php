<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class LocaleDescriptionTool implements ToolInterface
{
    public function name(): string
    {
        return 'locale-description-tool';
    }

    public function description(): string
    {
        return 'Tool for locale description default inference.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'lang' => [
                    'type' => 'string',
                    'description' => 'Locale code (e.g., en_US, ko_KR, ja_JP)',
                ],
            ],
            'required' => ['lang'],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'lang' => $arguments['lang'] ?? null,
        ];
    }
}
