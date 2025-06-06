<?php

namespace OPGG\LaravelMcpServer\Server\Request;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;

class PromptsGetHandler extends RequestHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::PROTOCOL;

    protected const HANDLE_METHOD = 'prompts/get';

    public function __construct(private PromptRepository $repository)
    {
        parent::__construct();
    }

    public function execute(string $method, ?array $params = null): array
    {
        $identifier = $params['identifier'] ?? null;
        $variables = $params['variables'] ?? [];
        if (! is_string($identifier)) {
            throw new JsonRpcErrorException(message: 'identifier is required', code: JsonRpcErrorCode::INVALID_REQUEST);
        }
        if (! is_array($variables)) {
            $variables = [];
        }

        $content = $this->repository->render($identifier, $variables);
        if ($content === null) {
            throw new JsonRpcErrorException(message: 'Prompt not found', code: JsonRpcErrorCode::INVALID_PARAMS);
        }

        return [
            'prompt' => $content,
        ];
    }
}
