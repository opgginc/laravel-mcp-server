<?php

namespace OPGG\LaravelMcpServer\Routing;

use InvalidArgumentException;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
use OPGG\LaravelMcpServer\Server\Request\ToolsCallHandler;

final class McpRouteBuilder
{
    public function __construct(
        private readonly McpEndpointRegistry $registry,
        private readonly string $endpointId,
    ) {}

    public function setName(string $name): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withName($name));

        return $this;
    }

    public function setVersion(string $version): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withVersion($version));

        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withTitle($title));

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withDescription($description));

        return $this;
    }

    public function setWebsiteUrl(?string $websiteUrl): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withWebsiteUrl($websiteUrl));

        return $this;
    }

    /**
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>  $icons
     */
    public function setIcons(array $icons): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withIcons($icons));

        return $this;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withInstructions($instructions));

        return $this;
    }

    /**
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>|null  $icons
     */
    public function setServerInfo(
        ?string $name = null,
        ?string $version = null,
        ?string $title = null,
        ?string $description = null,
        ?string $websiteUrl = null,
        ?array $icons = null,
        ?string $instructions = null,
    ): self {
        $this->mutate(function (McpEndpointDefinition $definition) use (
            $name,
            $version,
            $title,
            $description,
            $websiteUrl,
            $icons,
            $instructions
        ) {
            $updated = $definition;

            if ($name !== null) {
                $updated = $updated->withName($name);
            }
            if ($version !== null) {
                $updated = $updated->withVersion($version);
            }
            if ($title !== null) {
                $updated = $updated->withTitle($title);
            }
            if ($description !== null) {
                $updated = $updated->withDescription($description);
            }
            if ($websiteUrl !== null) {
                $updated = $updated->withWebsiteUrl($websiteUrl);
            }
            if ($icons !== null) {
                $updated = $updated->withIcons($icons);
            }
            if ($instructions !== null) {
                $updated = $updated->withInstructions($instructions);
            }

            return $updated;
        });

        return $this;
    }

    /**
     * @param  array<int, class-string>  $tools
     */
    public function tools(array $tools): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withTools($tools));

        return $this;
    }

    /**
     * @param  array<int, class-string>  $resources
     */
    public function resources(array $resources): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withResources($resources));

        return $this;
    }

    /**
     * @param  array<int, class-string>  $resourceTemplates
     */
    public function resourceTemplates(array $resourceTemplates): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withResourceTemplates($resourceTemplates));

        return $this;
    }

    /**
     * @param  array<int, class-string>  $prompts
     */
    public function prompts(array $prompts): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withPrompts($prompts));

        return $this;
    }

    /**
     * @param  class-string  $handlerClass
     */
    public function toolsCallHandler(string $handlerClass): self
    {
        if (! is_a(object_or_class: $handlerClass, class: ToolsCallHandler::class, allow_string: true)) {
            throw new InvalidArgumentException(sprintf(
                'The tools/call handler [%s] must extend %s.',
                $handlerClass,
                ToolsCallHandler::class,
            ));
        }

        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withToolsCallHandler($handlerClass));

        return $this;
    }

    public function toolListChanged(bool $enabled = true): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withToolListChanged($enabled));

        return $this;
    }

    public function resourcesSubscribe(bool $enabled = true): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withResourcesSubscribe($enabled));

        return $this;
    }

    public function resourcesListChanged(bool $enabled = true): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withResourcesListChanged($enabled));

        return $this;
    }

    public function promptsListChanged(bool $enabled = true): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withPromptsListChanged($enabled));

        return $this;
    }

    public function toolsPageSize(int $pageSize): self
    {
        $this->mutate(fn (McpEndpointDefinition $definition) => $definition->withToolsPageSize($pageSize));

        return $this;
    }

    public function endpointId(): string
    {
        return $this->endpointId;
    }

    private function mutate(callable $mutator): void
    {
        $current = $this->registry->find($this->endpointId);
        if (! $current) {
            if (app()->bound('log')) {
                app('log')->warning('Attempted to mutate an unknown MCP endpoint definition.', [
                    'endpoint_id' => $this->endpointId,
                ]);
            }

            return;
        }

        $updated = $mutator($current);
        if ($updated instanceof McpEndpointDefinition) {
            $this->registry->update($updated);

            if (app()->bound(McpServerFactory::class)) {
                app(McpServerFactory::class)->clearEndpointCache($this->endpointId);
            }
        }
    }
}
