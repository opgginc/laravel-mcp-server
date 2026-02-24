<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

enum CompactEnumSample: string
{
    case ALPHA = 'alpha';
    case BETA = 'beta';
    case GAMMA = 'gamma';
    case DELTA = 'delta';
}

class CompactEnumTool implements ToolInterface
{
    public function name(): string
    {
        return 'compact-enum-tool';
    }

    public function description(): string
    {
        return 'Tool that uses compact enum descriptions.';
    }

    public function inputSchema(): array
    {
        return [
            'mode' => JsonSchema::string()
                ->description('Mode')
                ->enum(CompactEnumSample::class)
                ->compact()
                ->required(),
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): mixed
    {
        return $arguments;
    }
}
