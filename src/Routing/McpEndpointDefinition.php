<?php

namespace OPGG\LaravelMcpServer\Routing;

final class McpEndpointDefinition
{
    public const DEFAULT_NAME = 'OP.GG MCP Server';

    public const DEFAULT_VERSION = '2.0.0';

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
        return $this->copy(['name' => $name]);
    }

    public function withVersion(string $version): self
    {
        return $this->copy(['version' => $version]);
    }

    public function withTitle(?string $title): self
    {
        return $this->copy(['title' => $title]);
    }

    public function withDescription(?string $description): self
    {
        return $this->copy(['description' => $description]);
    }

    public function withWebsiteUrl(?string $websiteUrl): self
    {
        return $this->copy(['websiteUrl' => $websiteUrl]);
    }

    /**
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>  $icons
     */
    public function withIcons(array $icons): self
    {
        return $this->copy(['icons' => array_values($icons)]);
    }

    public function withInstructions(?string $instructions): self
    {
        return $this->copy(['instructions' => $instructions]);
    }

    /**
     * @param  array<int, class-string>  $tools
     */
    public function withTools(array $tools): self
    {
        return $this->copy(['tools' => array_values($tools)]);
    }

    /**
     * @param  array<int, class-string>  $resources
     */
    public function withResources(array $resources): self
    {
        return $this->copy(['resources' => array_values($resources)]);
    }

    /**
     * @param  array<int, class-string>  $resourceTemplates
     */
    public function withResourceTemplates(array $resourceTemplates): self
    {
        return $this->copy(['resourceTemplates' => array_values($resourceTemplates)]);
    }

    /**
     * @param  array<int, class-string>  $prompts
     */
    public function withPrompts(array $prompts): self
    {
        return $this->copy(['prompts' => array_values($prompts)]);
    }

    public function withToolListChanged(bool $enabled): self
    {
        return $this->copy(['toolListChanged' => $enabled]);
    }

    public function withResourcesSubscribe(bool $enabled): self
    {
        return $this->copy(['resourcesSubscribe' => $enabled]);
    }

    public function withResourcesListChanged(bool $enabled): self
    {
        return $this->copy(['resourcesListChanged' => $enabled]);
    }

    public function withPromptsListChanged(bool $enabled): self
    {
        return $this->copy(['promptsListChanged' => $enabled]);
    }

    public function withToolsPageSize(int $pageSize): self
    {
        return $this->copy(['toolsPageSize' => max(1, $pageSize)]);
    }

    public static function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return '/';
        }

        return '/'.trim($trimmed, '/');
    }

    /**
     * @param  array{
     *   id?: string,
     *   path?: string,
     *   name?: string,
     *   version?: string,
     *   title?: ?string,
     *   description?: ?string,
     *   websiteUrl?: ?string,
     *   instructions?: ?string,
     *   icons?: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>,
     *   tools?: array<int, class-string>,
     *   resources?: array<int, class-string>,
     *   resourceTemplates?: array<int, class-string>,
     *   prompts?: array<int, class-string>,
     *   toolListChanged?: bool,
     *   resourcesSubscribe?: bool,
     *   resourcesListChanged?: bool,
     *   promptsListChanged?: bool,
     *   toolsPageSize?: int
     * }  $overrides
     */
    private function copy(array $overrides): self
    {
        $state = array_merge($this->state(), $overrides);

        return new self(
            id: $state['id'],
            path: $state['path'],
            name: $state['name'],
            version: $state['version'],
            title: $state['title'],
            description: $state['description'],
            websiteUrl: $state['websiteUrl'],
            instructions: $state['instructions'],
            icons: $state['icons'],
            tools: $state['tools'],
            resources: $state['resources'],
            resourceTemplates: $state['resourceTemplates'],
            prompts: $state['prompts'],
            toolListChanged: $state['toolListChanged'],
            resourcesSubscribe: $state['resourcesSubscribe'],
            resourcesListChanged: $state['resourcesListChanged'],
            promptsListChanged: $state['promptsListChanged'],
            toolsPageSize: $state['toolsPageSize'],
        );
    }

    /**
     * @return array{
     *   id: string,
     *   path: string,
     *   name: string,
     *   version: string,
     *   title: ?string,
     *   description: ?string,
     *   websiteUrl: ?string,
     *   instructions: ?string,
     *   icons: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>,
     *   tools: array<int, class-string>,
     *   resources: array<int, class-string>,
     *   resourceTemplates: array<int, class-string>,
     *   prompts: array<int, class-string>,
     *   toolListChanged: bool,
     *   resourcesSubscribe: bool,
     *   resourcesListChanged: bool,
     *   promptsListChanged: bool,
     *   toolsPageSize: int
     * }
     */
    private function state(): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'name' => $this->name,
            'version' => $this->version,
            'title' => $this->title,
            'description' => $this->description,
            'websiteUrl' => $this->websiteUrl,
            'instructions' => $this->instructions,
            'icons' => $this->icons,
            'tools' => $this->tools,
            'resources' => $this->resources,
            'resourceTemplates' => $this->resourceTemplates,
            'prompts' => $this->prompts,
            'toolListChanged' => $this->toolListChanged,
            'resourcesSubscribe' => $this->resourcesSubscribe,
            'resourcesListChanged' => $this->resourcesListChanged,
            'promptsListChanged' => $this->promptsListChanged,
            'toolsPageSize' => $this->toolsPageSize,
        ];
    }
}
