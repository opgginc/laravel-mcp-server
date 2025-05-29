<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

class ToolsCallHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;
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
        if (!$tool) {
            throw new JsonRpcErrorException(message: "Tool '{$name}' not found", code: JsonRpcErrorCode::METHOD_NOT_FOUND);
        }

        return $tool->messageType();
    }

    public function execute(string $method, ?array $params = null): array
    {
        $name = $params['name'] ?? null;
        if ($name === null) {
            throw new JsonRpcErrorException(message: 'Tool name is required', code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $tool = $this->toolRepository->getTool($name);
        if (!$tool) {
            throw new JsonRpcErrorException(message: "Tool '{$name}' not found", code: JsonRpcErrorCode::METHOD_NOT_FOUND);
        }

        $arguments = $params['arguments'] ?? [];
        $result = $tool->execute($arguments);

        if ($method === 'tools/call') {
            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ];
        } else {
            return [
                'result' => $result,
            ];
        }
    }
}
