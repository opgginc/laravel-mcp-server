<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

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
        $name = $params['name'] ?? null;
        if ($name === null) {
            throw new JsonRpcErrorException(message: 'Tool name is required', code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $tool = $this->toolRepository->getTool($name);
        if (! $tool) {
            throw new JsonRpcErrorException(message: "Tool '{$name}' not found", code: JsonRpcErrorCode::METHOD_NOT_FOUND);
        }

        // Check for new isStreaming() method first (v1.3.0+)
        if (method_exists($tool, 'isStreaming')) {
            return $tool->isStreaming() ? ProcessMessageType::SSE : ProcessMessageType::HTTP;
        }

        // Fallback to legacy messageType() method for backward compatibility
        if (method_exists($tool, 'messageType')) {
            return $tool->messageType();
        }

        // Default to HTTP if neither method exists
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

        if ($method === 'tools/call') {
            $structuredContent = $this->normalizeStructuredContent($result);
            $content = [];

            if ($structuredContent !== null) {
                $serialized = json_encode($structuredContent, JSON_UNESCAPED_UNICODE);
                if ($serialized === false) {
                    throw new JsonRpcErrorException(
                        message: 'Failed to encode structured tool response',
                        code: JsonRpcErrorCode::INTERNAL_ERROR
                    );
                }

                $content[] = [
                    'type' => 'text',
                    'text' => $serialized,
                ];

                /**
                 * MCP 2025-06-18 expects servers to populate both `content` and
                 * `structuredContent` when a tool produces JSON data. This keeps
                 * older clients functional while enabling schema validation.
                 *
                 * @see https://modelcontextprotocol.io/specification/2025-06-18/server/tools#tool-result
                 */
                return [
                    'content' => $content,
                    'structuredContent' => $structuredContent,
                    'isError' => false,
                ];
            }

            $content[] = [
                'type' => 'text',
                'text' => $this->stringifyResult($result),
            ];

            return [
                'content' => $content,
                'isError' => false,
            ];
        } else {
            return [
                'result' => $result,
            ];
        }
    }

    private function normalizeStructuredContent(mixed $result): ?array
    {
        if ($result instanceof Arrayable) {
            return $result->toArray();
        }

        if ($result instanceof JsonSerializable) {
            $serialized = $result->jsonSerialize();

            return is_array($serialized)
                ? $serialized
                : (is_object($serialized) ? (array) $serialized : null);
        }

        if (is_array($result)) {
            return $result;
        }

        if (is_object($result)) {
            $encoded = json_encode($result, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                return null;
            }

            $decoded = json_decode($encoded, true);

            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function stringifyResult(mixed $result): string
    {
        if (is_string($result)) {
            return $result;
        }

        if (is_scalar($result) || $result === null) {
            $encoded = json_encode($result, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new JsonRpcErrorException(
                    message: 'Failed to encode scalar tool response',
                    code: JsonRpcErrorCode::INTERNAL_ERROR
                );
            }

            return $encoded;
        }

        $encoded = json_encode($result, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new JsonRpcErrorException(
                message: 'Failed to encode complex tool response',
                code: JsonRpcErrorCode::INTERNAL_ERROR
            );
        }

        return $encoded;
    }
}
