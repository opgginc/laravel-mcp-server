<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class HelloWorldTool implements ToolInterface
{
    public function isStreaming(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'hello-world';
    }

    public function description(): string
    {
        return 'Say HelloWorld developer.';
    }

    public function inputSchema(): array
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

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            throw new JsonRpcErrorException(message: $validator->errors()->toJson(), code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $name = $arguments['name'] ?? 'MCP';

        return [
            'name' => $name,
            'message' => "Hello, HelloWorld `{$name}` developer.",
        ];
    }
}
