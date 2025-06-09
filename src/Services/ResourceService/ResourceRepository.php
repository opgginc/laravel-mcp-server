<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

use Illuminate\Container\Container;
use InvalidArgumentException;

class ResourceRepository
{
    /** @var array<string, resource> */
    protected array $resources = [];

    /** @var ResourceTemplate[] */
    protected array $templates = [];

    protected Container $container;

    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * @param  resource[]  $resources
     */
    public function registerResources(array $resources): self
    {
        foreach ($resources as $resource) {
            $this->registerResource($resource);
        }

        return $this;
    }

    public function registerResource(Resource|string $resource): self
    {
        if (is_string($resource)) {
            $resource = $this->container->make($resource);
        }

        if (! $resource instanceof Resource) {
            throw new InvalidArgumentException('Resource must extend '.Resource::class);
        }

        $this->resources[$resource->uri] = $resource;

        return $this;
    }

    /**
     * @param  ResourceTemplate[]  $templates
     */
    public function registerResourceTemplates(array $templates): self
    {
        foreach ($templates as $template) {
            $this->registerResourceTemplate($template);
        }

        return $this;
    }

    public function registerResourceTemplate(ResourceTemplate|string $template): self
    {
        if (is_string($template)) {
            $template = $this->container->make($template);
        }

        if (! $template instanceof ResourceTemplate) {
            throw new InvalidArgumentException('Template must extend '.ResourceTemplate::class);
        }

        $this->templates[] = $template;

        return $this;
    }

    /**
     * Get all available resources including static resources and template-generated resources.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResourceSchemas(): array
    {
        $staticResources = array_values(array_map(fn (Resource $r) => $r->toArray(), $this->resources));
        
        $templateResources = [];
        foreach ($this->templates as $template) {
            $listedResources = $template->list();
            if ($listedResources !== null) {
                foreach ($listedResources as $resource) {
                    $templateResources[] = $resource;
                }
            }
        }
        
        return array_merge($staticResources, $templateResources);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTemplateSchemas(): array
    {
        return array_values(array_map(fn (ResourceTemplate $t) => $t->toArray(), $this->templates));
    }

    /**
     * Read resource content by URI.
     *
     * This method first attempts to find a static resource with an exact URI match.
     * If no static resource is found, it tries to match the URI against registered
     * resource templates and returns the dynamically generated content.
     *
     * @param  string  $uri  The resource URI to read (e.g., "database://users/123")
     * @return array|null Resource content array with 'uri', 'mimeType', and 'text'/'blob', or null if not found
     */
    public function readResource(string $uri): ?array
    {
        // First, try to find a static resource with exact URI match
        $resource = $this->resources[$uri] ?? null;
        if ($resource !== null) {
            return $resource->read();
        }

        // If no static resource found, try to match against templates
        foreach ($this->templates as $template) {
            $params = $template->matchUri($uri);
            if ($params !== null) {
                return $template->read($uri, $params);
            }
        }

        return null;
    }
}
