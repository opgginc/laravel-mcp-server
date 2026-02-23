<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use JsonException;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;

class ToolsCallHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = ['tools/call', 'tools/execute'];

    private ToolRepository $toolRepository;

    public function __construct(ToolRepository $toolRepository)
    {
        parent::__construct();
        $this->toolRepository = $toolRepository;
    }

    public function getMessageType(?array $params = null): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function execute(string $method, ?array $params = null): array
    {
        $name = $params['name'] ?? null;
        if ($name === null) {
            throw new JsonRpcErrorException(message: 'Tool name is required', code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $tool = $this->toolRepository->getTool($name);
        if (! $tool) {
            throw new JsonRpcErrorException(message: "Tool '{$name}' not found", code: JsonRpcErrorCode::METHOD_NOT_FOUND);
        }

        $arguments = $params['arguments'] ?? [];
        $result = $tool->execute($arguments);

        $autoStructuredOutput = false;
        if (property_exists($tool, 'autoStructuredOutput')) {
            $autoStructuredOutput = (bool) (function () {
                return $this->autoStructuredOutput;
            })->call($tool);
        }

        $preparedResult = $result instanceof ToolResponse
            ? $result->toArray()
            : $result;

        if ($method !== 'tools/call') {
            return [
                'result' => $preparedResult,
            ];
        }

        if ($result instanceof ToolResponse) {
            return $this->normalizeCallToolResult($preparedResult);
        }

        if (is_array($preparedResult)) {
            if (array_key_exists('content', $preparedResult)
                || array_key_exists('structuredContent', $preparedResult)
                || array_key_exists('isError', $preparedResult)) {
                return $this->normalizeCallToolResult($preparedResult);
            }

            if ($autoStructuredOutput) {
                return $this->normalizeCallToolResult([
                    'structuredContent' => $preparedResult,
                ]);
            }

            return [
                'content' => $this->textContent($this->encodeJson($preparedResult)),
            ];
        }

        if (is_string($preparedResult)) {
            return [
                'content' => $this->textContent($preparedResult),
            ];
        }

        return [
            'content' => $this->textContent($this->encodeJson($preparedResult)),
        ];
    }

    /**
     * Normalize tools/call payloads to satisfy CallToolResult shape in MCP 2025-11-25.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeCallToolResult(array $payload): array
    {
        if (! array_key_exists('content', $payload)) {
            $payload['content'] = $this->fallbackContent($payload);

            return $payload;
        }

        if (! is_array($payload['content'])) {
            $payload['content'] = $this->textContent($this->encodeJson($payload['content']));

            return $payload;
        }

        $payload['content'] = array_values($payload['content']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array{type: string, text: string}>
     */
    private function fallbackContent(array $payload): array
    {
        if (array_key_exists('structuredContent', $payload)) {
            return $this->textContent($this->encodeJson($payload['structuredContent']));
        }

        $mirrorPayload = $payload;
        unset($mirrorPayload['content']);

        return $this->textContent($this->encodeJson($mirrorPayload));
    }

    /**
     * @return array<int, array{type: string, text: string}>
     */
    private function textContent(string $text): array
    {
        return [
            [
                'type' => 'text',
                'text' => $text,
            ],
        ];
    }

    /**
     * Ensure results remain JSON serializable while providing consistent error handling.
     */
    private function encodeJson(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new JsonRpcErrorException(
                message: 'Failed to encode tool result as JSON: '.$exception->getMessage(),
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }
}
