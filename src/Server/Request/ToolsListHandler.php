<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use Illuminate\Support\Facades\Config;
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

    public function __construct(ToolRepository $toolRepository)
    {
        parent::__construct();
        $this->toolRepository = $toolRepository;

        // Enforce the page size mandated by our configuration while defaulting to 50 entries.
        // This mirrors the cursor based pagination described in the MCP 2025-06-18 tools/list spec.
        $configuredPageSize = (int) Config::get('mcp-server.tools_list.page_size', 50);
        $this->pageSize = max(1, $configuredPageSize);
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
            // @see https://modelcontextprotocol.io/specification/2025-06-18#listing-tools
            'tools' => $page,
        ];

        $nextOffset = $offset + $this->pageSize;
        if ($nextOffset < count($schemas)) {
            $response['nextCursor'] = (string) $nextOffset;
        }

        return $response;
    }
}
