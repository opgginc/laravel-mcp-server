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

    private const PAGE_SIZE = 50;

    private ToolRepository $toolRepository;

    public function __construct(ToolRepository $toolRepository)
    {
        parent::__construct();
        $this->toolRepository = $toolRepository;
    }

    public function execute(string $method, ?array $params = null): array
    {
        $cursor = $params['cursor'] ?? null;
        $offset = 0;

        if ($cursor !== null) {
            if (is_string($cursor) && ctype_digit($cursor)) {
                $offset = (int) $cursor;
            } elseif (is_int($cursor) && $cursor >= 0) {
                $offset = $cursor;
            } else {
                throw new JsonRpcErrorException(
                    message: 'Invalid cursor provided for tools/list pagination',
                    code: JsonRpcErrorCode::INVALID_PARAMS
                );
            }
        }

        $schemas = $this->toolRepository->getToolSchemas();
        $paginated = array_slice($schemas, $offset, self::PAGE_SIZE);
        $hasMore = ($offset + self::PAGE_SIZE) < count($schemas);

        /**
         * Pagination behaviour follows MCP spec 2025-06-18 tools/list guidance, which
         * requires returning a nextCursor token when more data is available. The
         * cursor we emit is a simple numeric offset because the spec allows server
         * defined cursors. See "Listing Tools" section of the protocol revision.
         *
         * @see https://modelcontextprotocol.io/specification/2025-06-18/server/tools#listing-tools
         */
        return [
            'tools' => $paginated,
            'nextCursor' => $hasMore ? (string) ($offset + self::PAGE_SIZE) : null,
        ];
    }
}
