<?php

namespace OPGG\LaravelMcpServer\Routing;

final class McpEndpointDefinition
{
    public const DEFAULT_NAME = 'OP.GG MCP Server';

    public const DEFAULT_VERSION = '1.5.2';

    public const DEFAULT_TOOLS_PAGE_SIZE = 50;

    /**
     * @param  array<int, class-string>  $tools
     * @param  array<int, class-string>  $resources
     * @param  array<int, class-string>  $resourceTemplates
     * @param  array<int, class-string>  $prompts
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>  $icons
     */
    public function __construct(
        public readonly string $id,
        public readonly string $path,
        public readonly string $name = self::DEFAULT_NAME,
        public readonly string $version = self::DEFAULT_VERSION,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $websiteUrl = null,
        public readonly ?string $instructions = null,
        public readonly array $icons = [],
        public readonly array $tools = [],
        public readonly array $resources = [],
        public readonly array $resourceTemplates = [],
        public readonly array $prompts = [],
        public readonly bool $toolListChanged = false,
        public readonly bool $resourcesSubscribe = false,
        public readonly bool $resourcesListChanged = false,
        public readonly bool $promptsListChanged = false,
        public readonly int $toolsPageSize = self::DEFAULT_TOOLS_PAGE_SIZE,
    ) {}

    public static function create(string $id, string $path): self
    {
        return new self(id: $id, path: self::normalizePath($path));
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withVersion(string $version): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withTitle(?string $title): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withDescription(?string $description): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withWebsiteUrl(?string $websiteUrl): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    /**
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>  $icons
     */
    public function withIcons(array $icons): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: array_values($icons),
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withInstructions(?string $instructions): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    /**
     * @param  array<int, class-string>  $tools
     */
    public function withTools(array $tools): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: array_values($tools),
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    /**
     * @param  array<int, class-string>  $resources
     */
    public function withResources(array $resources): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: array_values($resources),
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    /**
     * @param  array<int, class-string>  $resourceTemplates
     */
    public function withResourceTemplates(array $resourceTemplates): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: array_values($resourceTemplates),
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    /**
     * @param  array<int, class-string>  $prompts
     */
    public function withPrompts(array $prompts): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: array_values($prompts),
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withToolListChanged(bool $enabled): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $enabled,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withResourcesSubscribe(bool $enabled): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $enabled,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withResourcesListChanged(bool $enabled): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $enabled,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withPromptsListChanged(bool $enabled): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $enabled,
            toolsPageSize: $this->toolsPageSize,
        );
    }

    public function withToolsPageSize(int $pageSize): self
    {
        return new self(
            id: $this->id,
            path: $this->path,
            name: $this->name,
            version: $this->version,
            title: $this->title,
            description: $this->description,
            websiteUrl: $this->websiteUrl,
            instructions: $this->instructions,
            icons: $this->icons,
            tools: $this->tools,
            resources: $this->resources,
            resourceTemplates: $this->resourceTemplates,
            prompts: $this->prompts,
            toolListChanged: $this->toolListChanged,
            resourcesSubscribe: $this->resourcesSubscribe,
            resourcesListChanged: $this->resourcesListChanged,
            promptsListChanged: $this->promptsListChanged,
            toolsPageSize: max(1, $pageSize),
        );
    }

    public static function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/'.trim($trimmed, '/');
    }
}
