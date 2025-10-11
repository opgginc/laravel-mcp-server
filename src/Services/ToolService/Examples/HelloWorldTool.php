<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\Validator;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

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

    public function title(): string
    {
        return 'Hello World Greeting';
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

    public function outputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Echoed developer name.',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'Greeting message returned to the caller.',
                ],
            ],
            'required' => ['name', 'message'],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): ToolResponse
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            throw new JsonRpcErrorException(message: $validator->errors()->toJson(), code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $name = $arguments['name'] ?? 'MCP';

        $payload = [
            'name' => $name,
            'message' => "Hello, HelloWorld `{$name}` developer.",
        ];

        // Provide both human readable and structured payloads per MCP 2025-06-18 guidance.
        // @see https://modelcontextprotocol.io/specification/2025-06-18#structured-content
        return ToolResponse::structured($payload, [
            [
                'type' => 'text',
                'text' => $payload['message'],
            ],
        ]);
    }
}
