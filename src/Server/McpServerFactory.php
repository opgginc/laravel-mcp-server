<?php

namespace OPGG\LaravelMcpServer\Server;

use Illuminate\Container\Container;
use InvalidArgumentException;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Transports\StreamableHttpTransport;

final class McpServerFactory
{
    /**
     * Cached tool-name to class map by endpoint ID.
     *
     * @var array<string, array<string, class-string<ToolInterface>>>
     */
    private array $toolClassMapByEndpoint = [];

    /**
     * Cached tool name by class for cross-endpoint reuse.
     *
     * @var array<class-string<ToolInterface>, string>
     */
    private array $toolNameByClass = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  array<string, mixed>|null  $requestMessage
     */
    public function make(McpEndpointDefinition $endpoint, ?array $requestMessage = null): MCPServer
    {
        $requestedMethod = $this->requestedMethod($requestMessage);

        $capabilities = new ServerCapabilities;
        $capabilities->withTools([
            'listChanged' => $endpoint->toolListChanged,
        ]);
        $capabilities->withResources([
            'subscribe' => $endpoint->resourcesSubscribe,
            'listChanged' => $endpoint->resourcesListChanged,
        ]);
        $capabilities->withPrompts([
            'listChanged' => $endpoint->promptsListChanged,
        ]);

        $server = MCPServer::create(
            new MCPProtocol(new StreamableHttpTransport),
            $endpoint->name,
            $endpoint->version,
            $capabilities,
            $endpoint->title,
            $endpoint->description,
            $endpoint->websiteUrl,
            $endpoint->icons,
            $endpoint->instructions,
        );

        if ($this->supportsToolsMethod($requestedMethod)) {
            $toolRepository = new ToolRepository($this->container);

            if ($this->isToolCallMethod($requestedMethod)) {
                $requestedToolName = $this->requestedToolName($requestMessage);
                if ($requestedToolName !== null) {
                    $toolClass = $this->resolveToolClassByName($endpoint, $requestedToolName);
                    if ($toolClass !== null) {
                        $toolRepository->register($toolClass);
                    }
                }
            } elseif ($this->isToolsListMethod($requestedMethod)) {
                $toolRepository->registerSchemaMany($endpoint->tools);
            } else {
                $toolRepository->registerMany($endpoint->tools);
            }

            $server->registerToolRepository($toolRepository, $endpoint->toolsPageSize);
        }

        if ($this->supportsResourcesMethod($requestedMethod)) {
            $resourceRepository = new ResourceRepository($this->container);
            foreach ($endpoint->resources as $resourceClass) {
                $resourceRepository->registerResource($resourceClass);
            }
            foreach ($endpoint->resourceTemplates as $resourceTemplateClass) {
                $resourceRepository->registerResourceTemplate($resourceTemplateClass);
            }

            $server->registerResourceRepository($resourceRepository, $endpoint->resourcesSubscribe);
        }

        if ($this->supportsPromptsMethod($requestedMethod)) {
            $promptRepository = new PromptRepository($this->container);
            foreach ($endpoint->prompts as $promptClass) {
                $promptRepository->registerPrompt($promptClass);
            }

            $server->registerPromptRepository($promptRepository);
        }

        return $server;
    }

    private function supportsToolsMethod(?string $requestedMethod): bool
    {
        return is_string($requestedMethod) && str_starts_with($requestedMethod, 'tools/');
    }

    private function isToolCallMethod(?string $requestedMethod): bool
    {
        return $requestedMethod === 'tools/call' || $requestedMethod === 'tools/execute';
    }

    private function isToolsListMethod(?string $requestedMethod): bool
    {
        return $requestedMethod === 'tools/list';
    }

    private function supportsResourcesMethod(?string $requestedMethod): bool
    {
        return is_string($requestedMethod) && str_starts_with($requestedMethod, 'resources/');
    }

    private function supportsPromptsMethod(?string $requestedMethod): bool
    {
        return is_string($requestedMethod) && str_starts_with($requestedMethod, 'prompts/');
    }

    /**
     * @param  array<string, mixed>|null  $requestMessage
     */
    private function requestedMethod(?array $requestMessage): ?string
    {
        $method = $requestMessage['method'] ?? null;

        return is_string($method) ? $method : null;
    }

    /**
     * @param  array<string, mixed>|null  $requestMessage
     */
    private function requestedToolName(?array $requestMessage): ?string
    {
        $params = $requestMessage['params'] ?? null;
        if (! is_array($params)) {
            return null;
        }

        $name = $params['name'] ?? null;

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function resolveToolClassByName(McpEndpointDefinition $endpoint, string $toolName): ?string
    {
        $endpointId = $endpoint->id;
        if (! isset($this->toolClassMapByEndpoint[$endpointId])) {
            $this->toolClassMapByEndpoint[$endpointId] = $this->buildToolClassMap($endpoint);
        }

        return $this->toolClassMapByEndpoint[$endpointId][$toolName] ?? null;
    }

    /**
     * @return array<string, class-string<ToolInterface>>
     */
    private function buildToolClassMap(McpEndpointDefinition $endpoint): array
    {
        $map = [];
        foreach ($endpoint->tools as $toolClass) {
            $toolName = $this->toolNameByClass[$toolClass] ??= $this->resolveToolName($toolClass);
            $map[$toolName] = $toolClass;
        }

        return $map;
    }

    /**
     * @param  class-string  $toolClass
     */
    private function resolveToolName(string $toolClass): string
    {
        $tool = $this->container->make($toolClass);
        if (! $tool instanceof ToolInterface) {
            throw new InvalidArgumentException('Tool must implement the '.ToolInterface::class);
        }

        return $tool->name();
    }
}
