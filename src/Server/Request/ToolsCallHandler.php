<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Utils\TabularDataFormatter;

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

        $tabularMeta = null;
        if (is_array($result) && array_key_exists(TabularDataFormatter::META_KEY, $result)) {
            $tabularMeta = $result[TabularDataFormatter::META_KEY] ?? null;
            unset($result[TabularDataFormatter::META_KEY]);
        }

        if ($method === 'tools/call') {
            $content = [
                [
                    'type' => 'text',
                    'text' => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
                ],
            ];

            if (is_array($tabularMeta)) {
                $rows = $tabularMeta['rows'] ?? [];
                $headers = $tabularMeta['headers'] ?? null;
                $delimiter = is_string($tabularMeta['delimiter'] ?? null)
                    ? $tabularMeta['delimiter']
                    : ',';
                $includeMarkdown = (bool) ($tabularMeta['include_markdown'] ?? true);

                if (TabularDataFormatter::isTabular($rows, $headers)) {
                    $resolvedHeaders = TabularDataFormatter::resolveHeaders($rows, $headers);
                    $normalizedRows = TabularDataFormatter::normalizeRows($rows, $resolvedHeaders);

                    $content[] = [
                        'type' => 'text',
                        'text' => TabularDataFormatter::toCsv($normalizedRows, $resolvedHeaders, $delimiter),
                        'annotations' => [
                            'mimeType' => 'text/csv',
                        ],
                    ];

                    if ($includeMarkdown) {
                        $content[] = [
                            'type' => 'text',
                            'text' => TabularDataFormatter::toMarkdown($normalizedRows, $resolvedHeaders),
                            'annotations' => [
                                'mimeType' => 'text/markdown',
                            ],
                        ];
                    }
                }
            }

            return [
                'content' => $content,
            ];
        } else {
            return [
                'result' => $result,
            ];
        }
    }
}
