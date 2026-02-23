<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

class ToolsListHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'tools/list';

    private ToolRepository $toolRepository;

    private int $pageSize;

    public function __construct(ToolRepository $toolRepository, int $pageSize = 50)
    {
        parent::__construct();
        $this->toolRepository = $toolRepository;
        $this->pageSize = max(1, $pageSize);
    }

    public function execute(string $method, ?array $params = null): array
    {
        $schemas = array_values($this->toolRepository->getToolSchemas());

        $cursor = $params['cursor'] ?? null;
        $offset = 0;

        if ($cursor !== null) {
            if (! is_string($cursor) || $cursor === '') {
                throw new JsonRpcErrorException('Cursor must be a non-empty string.', JsonRpcErrorCode::INVALID_PARAMS);
            }

            if (! ctype_digit($cursor)) {
                throw new JsonRpcErrorException('Cursor must be a numeric string offset.', JsonRpcErrorCode::INVALID_PARAMS);
            }

            $offset = (int) $cursor;
        }

        $page = array_slice($schemas, $offset, $this->pageSize);

        $response = [
            // The spec requires returning the tool definitions in a `tools` array.
            // @see https://modelcontextprotocol.io/specification/2025-11-25/schema
            'tools' => $page,
        ];

        $nextOffset = $offset + $this->pageSize;
        if ($nextOffset < count($schemas)) {
            $response['nextCursor'] = (string) $nextOffset;
        }

        return $response;
    }
}
