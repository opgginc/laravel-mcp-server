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

        $autoStructuredOutput = false;
        if (property_exists($tool, 'autoStructuredOutput')) {
            $autoStructuredOutput = (bool) (function () {
                return $this->autoStructuredOutput;
            })->call($tool);
        }

        $preparedResult = $result instanceof ToolResponse
            ? $result->toArray()
            : $result;

        if ($method === 'tools/call') {
            if ($result instanceof ToolResponse) {
                return $preparedResult;
            }

            if (is_array($preparedResult)) {
                if (array_key_exists('content', $preparedResult)
                    || array_key_exists('structuredContent', $preparedResult)
                    || array_key_exists('isError', $preparedResult)) {
                    return $preparedResult;
                }

                if ($autoStructuredOutput) {
                    try {
                        json_encode($preparedResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    } catch (JsonException $exception) {
                        throw new JsonRpcErrorException(
                            message: 'Failed to encode tool result as JSON: '.$exception->getMessage(),
                            code: JsonRpcErrorCode::INTERNAL_ERROR
                        );
                    }

                    return [
                        'structuredContent' => $preparedResult,
                    ];
                }

                try {
                    $text = json_encode($preparedResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                } catch (JsonException $exception) {
                    throw new JsonRpcErrorException(
                        message: 'Failed to encode tool result as JSON: '.$exception->getMessage(),
                        code: JsonRpcErrorCode::INTERNAL_ERROR
                    );
                }

                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text,
                        ],
                    ],
                ];
            }

            if (is_string($preparedResult)) {
                return [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $preparedResult,
                        ],
                    ],
                ];
            }

            try {
                $text = json_encode($preparedResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $exception) {
                throw new JsonRpcErrorException(
                    message: 'Failed to encode tool result as JSON: '.$exception->getMessage(),
                    code: JsonRpcErrorCode::INTERNAL_ERROR
                );
            }

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
            ];
        } else {
            return [
                'result' => $preparedResult,
            ];
        }
    }
}
