<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\Concerns\FormatsTabularToolResponses;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

class HelloWorldTool implements ToolInterface
{
    use FormatsTabularToolResponses;

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

    /**
     * @return array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>
     */
    public function icons(): array
    {
        return [
            [
                'src' => 'https://example.com/icons/hello-world.png',
                'mimeType' => 'image/png',
                'sizes' => ['256x256'],
                'theme' => 'light',
            ],
        ];
    }

    public function inputSchema(): array
    {
        return [
            'name' => JsonSchema::string()
                ->description('Developer Name')
                ->required(),
            'platform' => JsonSchema::string()
                ->enum(Platform::class)
                ->description('Client platform'),
        ];
    }

    public function outputSchema(): array
    {
        return [
            'name' => JsonSchema::string()
                ->description('Echoed developer name.')
                ->required(),
            'message' => JsonSchema::string()
                ->description('Greeting message returned to the caller.')
                ->required(),
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
            'platform' => ['nullable', Rule::enum(Platform::class)],
        ]);
        if ($validator->fails()) {
            throw new JsonRpcErrorException(message: $validator->errors()->toJson(), code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $name = $arguments['name'] ?? 'MCP';

        $payload = [
            'name' => $name,
            'message' => "Hello, HelloWorld `{$name}` developer.",
        ];

        // Provide both human readable and structured payloads per MCP schema guidance.
        // @see https://modelcontextprotocol.io/specification/2025-11-25/schema
        return ToolResponse::structured($payload, [
            [
                'type' => 'text',
                'text' => $payload['message'],
            ],
        ]);
    }
}
