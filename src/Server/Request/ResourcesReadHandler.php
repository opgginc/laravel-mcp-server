<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

class ResourcesReadHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;

    protected const HANDLE_METHOD = 'resources/read';

    private ResourceRepository $resourceRepository;

    public function __construct(ResourceRepository $resourceRepository)
    {
        parent::__construct();
        $this->resourceRepository = $resourceRepository;
    }

    public function execute(string $method, ?array $params = null): array
    {
        $uri = $params['uri'] ?? null;
        if ($uri === null) {
            throw new JsonRpcErrorException('Resource uri is required', JsonRpcErrorCode::INVALID_REQUEST);
        }

        $uris = is_array($uri) ? $uri : [$uri];
        $contents = [];
        foreach ($uris as $u) {
            $resource = $this->resourceRepository->getResource($u);
            if (! $resource) {
                throw new JsonRpcErrorException("Resource `{$u}` not found", JsonRpcErrorCode::METHOD_NOT_FOUND);
            }
            $contents[] = $resource->read();
        }

        return ['contents' => $contents];
    }
}
