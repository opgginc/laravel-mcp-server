<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;

class PromptsListHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;

    protected const HANDLE_METHOD = 'prompts/list';

    public function __construct(private PromptRepository $repository)
    {
        parent::__construct();
    }

    public function execute(string $method, ?array $params = null): array
    {
        return [
            'prompts' => $this->repository->getPromptSchemas(),
        ];
    }
}
