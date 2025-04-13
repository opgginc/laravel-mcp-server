<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

class ToolsListHandler implements RequestHandler
{
    private ToolRepository $toolRepository;

    public function __construct(ToolRepository $toolRepository)
    {
        $this->toolRepository = $toolRepository;
    }

    public function isHandle(string $method): bool
    {
        return $method === 'tools/list';
    }

    public function execute(string $method, ?array $params = null): array
    {
        return [
            'tools' => $this->toolRepository->getToolSchemas(),
        ];
    }
}
