<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

class ResourcesListHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;

    protected const HANDLE_METHOD = 'resources/list';

    public function __construct(private ResourceRepository $repository)
    {
        parent::__construct();
    }

    public function execute(string $method, ?array $params = null): array
    {
        return [
            'resources' => $this->repository->getResourceSchemas(),
            'resourceTemplates' => $this->repository->getTemplateSchemas(),
        ];
    }
}
