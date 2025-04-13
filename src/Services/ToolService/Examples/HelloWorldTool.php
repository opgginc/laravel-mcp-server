<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class HelloWorldTool implements ToolInterface
{
    public function getName(): string
    {
        return 'hello-world';
    }

    public function getDescription(): string
    {
        return 'Say HelloWorld developer.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Developer Name',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function getAnnotations(): array
    {
        return [];
    }

    public function execute(array $arguments): string
    {
        Validator::make($arguments, [
            'name' => ['required', 'string'],
        ])->validate();

        $name = $arguments['name'] ?? 'MCP';

        return "Hello, HelloWorld `{$name}` developer.";
    }
}
