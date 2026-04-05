<?php

namespace OPGG\LaravelMcpServer\Server;

use Illuminate\Container\Container;
use InvalidArgumentException;
use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Server\Request\ToolsCallHandler;
use OPGG\LaravelMcpServer\Server\Request\ToolsListHandler;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;
use OPGG\LaravelMcpServer\Services\ToolService\EndpointToolCatalog;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Transports\StreamableHttpTransport;

final class McpServerFactory
{
    /**
     * Cached tool-name to class map by endpoint ID and filtered tool fingerprint.
     *
     * @var array<string, array<string, array<string, class-string<ToolInterface>>>>
     */
    private array $toolClassMapByEndpoint = [];

    /**
     * Cached tool name by class for cross-endpoint reuse.
     *
     * @var array<class-string<ToolInterface>, string>
     */
    private array $toolNameByClass = [];

    public function __construct(
        private readonly Container $container,
        private readonly EndpointToolCatalog $endpointToolCatalog,
    ) {}

    /**
     * Clears all endpoint and class caches.
     * Useful in test environments and dynamic route reconfiguration scenarios.
     */
    public function clearCache(): void
    {
        $this->toolClassMapByEndpoint = [];
        $this->toolNameByClass = [];
    }

    /**
     * Clears endpoint-local caches while preserving cross-endpoint class metadata.
     */
    public function clearEndpointCache(string $endpointId): void
    {
        unset($this->toolClassMapByEndpoint[$endpointId]);
    }

    /**
     * @param  array<string, mixed>|null  $requestMessage
     * @param  array<int, class-string<ToolInterface>>|null  $resolvedToolClasses
     */
    public function make(
        McpEndpointDefinition $endpoint,
        ?array $requestMessage = null,
        ?ToolResolutionContext $toolResolutionContext = null,
        ?array $resolvedToolClasses = null,
    ): MCPServer {
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
            $endpoint->protocolVersion,
        );

        // Intentionally lazy-register repositories by method namespace to avoid
        // constructing unnecessary services for initialize/ping/notifications.
        if ($this->supportsToolsMethod($requestedMethod)) {
            $toolClasses = $resolvedToolClasses ?? $this->endpointToolCatalog->visibleToolClasses($endpoint, $toolResolutionContext);
            $toolRepository = new ToolRepository(
                $this->container,
                $endpoint->compactEnumExampleCount,
            );

            if ($this->isToolCallMethod($requestedMethod)) {
                $requestedToolName = $this->requestedToolName($requestMessage);
                if ($requestedToolName !== null) {
                    $toolClass = $this->resolveToolClassByName($endpoint, $toolClasses, $requestedToolName);
                    if ($toolClass !== null) {
                        $toolRepository->register($toolClass);
                    }
                }
            } elseif ($this->isToolsListMethod($requestedMethod)) {
                $toolRepository->registerSchemaMany($toolClasses);
            } else {
                $toolRepository->registerMany($toolClasses);
            }

            if ($endpoint->toolsCallHandler === null) {
                $server->registerToolRepository($toolRepository, $endpoint->toolsPageSize);
            } else {
                if ($this->isToolsListMethod($requestedMethod)) {
                    $server->registerRequestHandler(new ToolsListHandler(
                        toolRepository: $toolRepository,
                        pageSize: $endpoint->toolsPageSize,
                    ));
                }

                if ($this->isToolCallMethod($requestedMethod)) {
                    if (! is_a(
                        object_or_class: $endpoint->toolsCallHandler,
                        class: ToolsCallHandler::class,
                        allow_string: true
                    )) {
                        throw new InvalidArgumentException(sprintf(
                            'The tools/call handler [%s] must extend %s.',
                            $endpoint->toolsCallHandler,
                            ToolsCallHandler::class,
                        ));
                    }

                    $toolsCallHandler = $this->container->make(
                        abstract: $endpoint->toolsCallHandler,
                        parameters: ['toolRepository' => $toolRepository],
                    );

                    if (! $toolsCallHandler instanceof ToolsCallHandler) {
                        throw new InvalidArgumentException(sprintf(
                            'The resolved tools/call handler [%s] must extend %s.',
                            $endpoint->toolsCallHandler,
                            ToolsCallHandler::class,
                        ));
                    }

                    $server->registerRequestHandler($toolsCallHandler);
                }
            }
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

    /**
     * @param  array<int, class-string<ToolInterface>>  $toolClasses
     */
    private function resolveToolClassByName(
        McpEndpointDefinition $endpoint,
        array $toolClasses,
        string $toolName
    ): ?string {
        if ($endpoint->dynamicToolsResolver !== null) {
            return $this->buildToolClassMap($toolClasses)[$toolName] ?? null;
        }

        $endpointId = $endpoint->id;
        $fingerprint = $this->toolClassFingerprint($toolClasses);

        if (! isset($this->toolClassMapByEndpoint[$endpointId][$fingerprint])) {
            $this->toolClassMapByEndpoint[$endpointId][$fingerprint] = $this->buildToolClassMap($toolClasses);
        }

        return $this->toolClassMapByEndpoint[$endpointId][$fingerprint][$toolName] ?? null;
    }

    /**
     * @return array<string, class-string<ToolInterface>>
     */
    private function buildToolClassMap(array $toolClasses): array
    {
        $map = [];
        foreach ($toolClasses as $toolClass) {
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

    /**
     * @param  array<int, class-string<ToolInterface>>  $toolClasses
     */
    private function toolClassFingerprint(array $toolClasses): string
    {
        return sha1(implode("\n", $toolClasses));
    }
}
