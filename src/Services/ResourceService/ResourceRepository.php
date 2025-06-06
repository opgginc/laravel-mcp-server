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
     * @return array<int, array<string, mixed>>
     */
    public function getResourceSchemas(): array
    {
        return array_values(array_map(fn (Resource $r) => $r->toArray(), $this->resources));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTemplateSchemas(): array
    {
        return array_values(array_map(fn (ResourceTemplate $t) => $t->toArray(), $this->templates));
    }

    public function read(string $uri): ?array
    {
        $resource = $this->resources[$uri] ?? null;

        return $resource?->read();
    }
}
