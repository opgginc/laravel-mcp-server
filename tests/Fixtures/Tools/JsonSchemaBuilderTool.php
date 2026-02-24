<?php

namespace OPGG\LaravelMcpServer\Tests\Fixtures\Tools;

use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class JsonSchemaBuilderTool implements ToolInterface
{
    public function name(): string
    {
        return 'json-schema-builder-tool';
    }

    public function description(): string
    {
        return 'Tool that defines schemas using OPGG JsonSchema builder types.';
    }

    public function inputSchema(): array
    {
        return [
            'location' => JsonSchema::string()
                ->description('Location to get weather for')
                ->required(),
            'units' => JsonSchema::string()
                ->enum(['celsius', 'fahrenheit'])
                ->description('Temperature units')
                ->default('celsius'),
            'days' => JsonSchema::integer()
                ->min(1)
                ->max(7)
                ->nullable(),
        ];
    }

    public function outputSchema(): array
    {
        return [
            'forecast' => JsonSchema::string()->required(),
            'temperature' => JsonSchema::number()->required(),
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        return [
            'forecast' => 'sunny',
            'temperature' => 23.5,
            'echo' => $arguments,
        ];
    }
}
