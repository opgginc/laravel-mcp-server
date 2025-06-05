<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

use InvalidArgumentException;

class ResourceRepository
{
    /** @var array<string, \OPGG\LaravelMcpServer\Services\ResourceService\Resource> */
    protected array $resources = [];

    /** @var ResourceTemplate[] */
    protected array $templates = [];

    /**
     * @param  array<string|\OPGG\LaravelMcpServer\Services\ResourceService\Resource>  $resources
     */
    public function registerMany(array $resources): self
    {
        foreach ($resources as $resource) {
            $this->register($resource);
        }

        return $this;
    }

    public function register(string|Resource $resource): self
    {
        if (is_string($resource)) {
            if (! class_exists($resource)) {
                throw new InvalidArgumentException("Resource class {$resource} does not exist");
            }
            $resource = new $resource;
        }
        if (! $resource instanceof Resource) {
            throw new InvalidArgumentException('Resource must be an instance of '.Resource::class);
        }
        $this->resources[$resource->metadata()['uri']] = $resource;

        return $this;
    }

    public function registerTemplate(ResourceTemplate $template): self
    {
        $this->templates[] = $template;

        return $this;
    }

    /**
     * @return \OPGG\LaravelMcpServer\Services\ResourceService\Resource[]
     */
    public function getResources(): array
    {
        return array_values($this->resources);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getResourceMetadatas(): array
    {
        return array_values(array_map(fn (Resource $r) => $r->metadata(), $this->resources));
    }

    /**
     * @return ResourceTemplate[]
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTemplateMetadatas(): array
    {
        return array_values(array_map(fn (ResourceTemplate $t) => $t->toArray(), $this->templates));
    }

    public function getResource(string $uri): ?Resource
    {
        return $this->resources[$uri] ?? null;
    }
}
