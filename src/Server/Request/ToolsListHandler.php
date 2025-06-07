<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

class ToolsListHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'tools/list';

    private ToolRepository $toolRepository;

    public function __construct(ToolRepository $toolRepository)
    {
        parent::__construct();
        $this->toolRepository = $toolRepository;
    }

    public function execute(string $method, ?array $params = null): array
    {
        return [
            'tools' => $this->toolRepository->getToolSchemas(),
        ];
    }
}
