<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

class ResourcesListHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;

    protected const HANDLE_METHOD = 'resources/list';

    private ResourceRepository $resourceRepository;

    public function __construct(ResourceRepository $resourceRepository)
    {
        parent::__construct();
        $this->resourceRepository = $resourceRepository;
    }

    public function execute(string $method, ?array $params = null): array
    {
        return [
            'resources' => $this->resourceRepository->getResourceMetadatas(),
            'resourceTemplates' => $this->resourceRepository->getTemplateMetadatas(),
        ];
    }
}
