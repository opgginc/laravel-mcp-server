<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

class ResourcesReadHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'resources/read';

    public function __construct(private ResourceRepository $repository)
    {
        parent::__construct();
    }

    public function execute(string $method, ?array $params = null): array
    {
        $uri = $params['uri'] ?? null;
        if (! is_string($uri)) {
            throw new JsonRpcErrorException(message: 'uri is required', code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $content = $this->repository->readResource($uri);
        if ($content === null) {
            throw new JsonRpcErrorException(message: 'Resource not found', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        return [
            'contents' => [$content],
        ];
    }
}
