<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Utils\JsonSchemaValidator;

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

        // Check if tool supports output schema validation
        $hasOutputSchema = method_exists($tool, 'outputSchema') && $tool->outputSchema() !== null;

        if ($hasOutputSchema) {
            $outputSchema = $tool->outputSchema();
            $validation = JsonSchemaValidator::validateWithResult($result, $outputSchema);

            if (! $validation['valid']) {
                throw new JsonRpcErrorException(
                    message: "Tool output validation failed: {$validation['error']}",
                    code: JsonRpcErrorCode::INTERNAL_ERROR
                );
            }
        }

        if ($method === 'tools/call') {
            return [
                'content' => $this->formatToolContent($result, $hasOutputSchema),
            ];
        } else {
            return [
                'result' => $result,
            ];
        }
    }

    /**
     * Formats tool result into appropriate content structure.
     *
     * @param  mixed  $result  The tool execution result
     * @param  bool  $hasOutputSchema  Whether the tool has an output schema
     * @return array Content array for MCP response
     */
    private function formatToolContent(mixed $result, bool $hasOutputSchema): array
    {
        if ($hasOutputSchema && (is_array($result) || is_object($result))) {
            // Use structured content for schema-validated results
            return [
                [
                    'type' => 'structured',
                    'structuredContent' => $result,
                ],
            ];
        } else {
            // Fallback to text content for backward compatibility
            return [
                [
                    'type' => 'text',
                    'text' => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
                ],
            ];
        }
    }
}
