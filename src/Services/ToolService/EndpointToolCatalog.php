<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use Illuminate\Container\Container;
use InvalidArgumentException;
use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;

final class EndpointToolCatalog
{
    /**
     * @var array<class-string<ToolInterface>, string>
     */
    private array $toolNameByClass = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @return array<int, class-string<ToolInterface>>
     */
    public function declaredToolClasses(McpEndpointDefinition $endpoint): array
    {
        $this->assertConfigurationIsNotMixed($endpoint);

        if ($endpoint->dynamicToolsResolver === null) {
            return array_values(array_unique($endpoint->tools));
        }

        return $this->normalizeToolClasses(
            $this->resolver($endpoint->dynamicToolsResolver)->declaredTools($endpoint),
            'declared tools'
        );
    }

    /**
     * @return array<int, class-string<ToolInterface>>
     */
    public function visibleToolClasses(McpEndpointDefinition $endpoint, ?ToolResolutionContext $context = null): array
    {
        $declaredToolClasses = $this->declaredToolClasses($endpoint);
        if ($endpoint->dynamicToolsResolver === null) {
            return $declaredToolClasses;
        }

        $resolvedToolClasses = $this->normalizeToolClasses(
            $this->resolver($endpoint->dynamicToolsResolver)->resolve(
                $endpoint,
                $context ?? new ToolResolutionContext,
            ),
            'resolved tools'
        );

        $declaredToolLookup = [];
        foreach ($declaredToolClasses as $toolClass) {
            $declaredToolLookup[$toolClass] = true;
        }

        $allowedToolClasses = [];
        foreach ($resolvedToolClasses as $toolClass) {
            if (! isset($declaredToolLookup[$toolClass])) {
                throw new InvalidArgumentException(sprintf(
                    'The dynamic tools resolver [%s] returned undeclared tool [%s].',
                    $endpoint->dynamicToolsResolver,
                    $toolClass,
                ));
            }

            $allowedToolClasses[$toolClass] = true;
        }

        $visibleToolClasses = [];
        foreach ($declaredToolClasses as $toolClass) {
            if (isset($allowedToolClasses[$toolClass])) {
                $visibleToolClasses[] = $toolClass;
            }
        }

        return $visibleToolClasses;
    }

    public function declaresToolName(McpEndpointDefinition $endpoint, string $toolName): bool
    {
        return $this->containsToolName(
            toolClasses: $this->declaredToolClasses($endpoint),
            toolName: $toolName,
        );
    }

    public function exposesToolName(
        McpEndpointDefinition $endpoint,
        string $toolName,
        ?ToolResolutionContext $context = null,
    ): bool {
        return $this->containsToolName(
            toolClasses: $this->visibleToolClasses($endpoint, $context),
            toolName: $toolName,
        );
    }

    private function assertConfigurationIsNotMixed(McpEndpointDefinition $endpoint): void
    {
        if ($endpoint->tools !== [] && $endpoint->dynamicToolsResolver !== null) {
            throw new InvalidArgumentException(sprintf(
                'MCP endpoint [%s] cannot declare both static tools and a dynamic tools resolver.',
                $endpoint->path,
            ));
        }
    }

    private function resolver(string $resolverClass): DynamicToolResolverInterface
    {
        if (! is_a(object_or_class: $resolverClass, class: DynamicToolResolverInterface::class, allow_string: true)) {
            throw new InvalidArgumentException(sprintf(
                'The dynamic tools resolver [%s] must implement %s.',
                $resolverClass,
                DynamicToolResolverInterface::class,
            ));
        }

        $resolver = $this->container->make($resolverClass);
        if (! $resolver instanceof DynamicToolResolverInterface) {
            throw new InvalidArgumentException(sprintf(
                'The resolved dynamic tools resolver [%s] must implement %s.',
                $resolverClass,
                DynamicToolResolverInterface::class,
            ));
        }

        return $resolver;
    }

    /**
     * @param  array<int, mixed>  $toolClasses
     * @return array<int, class-string<ToolInterface>>
     */
    private function normalizeToolClasses(array $toolClasses, string $source): array
    {
        $normalizedToolClasses = [];

        foreach ($toolClasses as $toolClass) {
            if (! is_string($toolClass) || $toolClass === '') {
                throw new InvalidArgumentException(sprintf(
                    'The dynamic tools resolver returned an invalid %s entry.',
                    $source,
                ));
            }

            $normalizedToolClasses[$toolClass] = $toolClass;
        }

        return array_values($normalizedToolClasses);
    }

    /**
     * @param  array<int, class-string<ToolInterface>>  $toolClasses
     */
    private function containsToolName(array $toolClasses, string $toolName): bool
    {
        foreach ($toolClasses as $toolClass) {
            if ($this->resolveToolName($toolClass) === $toolName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  class-string<ToolInterface>  $toolClass
     */
    private function resolveToolName(string $toolClass): string
    {
        $tool = $this->container->make($toolClass);
        if (! $tool instanceof ToolInterface) {
            throw new InvalidArgumentException('Tool must implement the '.ToolInterface::class);
        }

        return $this->toolNameByClass[$toolClass] ??= $tool->name();
    }
}
