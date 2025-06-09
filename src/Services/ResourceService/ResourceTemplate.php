<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

use OPGG\LaravelMcpServer\Utils\UriTemplateUtil;

/**
 * Represents a URI template that can be expanded by clients to access dynamic resources.
 */
abstract class ResourceTemplate
{
    /**
     * URI template following RFC 6570.
     */
    public string $uriTemplate;

    /**
     * Human readable name for the template.
     */
    public string $name;

    /**
     * Optional description about what this template exposes.
     */
    public ?string $description = null;

    /**
     * Optional common MIME type for all resources matching this template.
     */
    public ?string $mimeType = null;

    public function toArray(): array
    {
        return array_filter([
            'uriTemplate' => $this->uriTemplate,
            'name' => $this->name,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
        ], static fn ($v) => $v !== null);
    }

    /**
     * Check if this template matches the given URI and extract parameters.
     *
     * @param  string  $uri  The URI to match against this template
     * @return array|null Array of parameters if match, null if no match
     */
    public function matchUri(string $uri): ?array
    {
        return UriTemplateUtil::matchUri($this->uriTemplate, $uri);
    }

    /**
     * List all resources that match this template pattern.
     *
     * This method is called when clients request the resources/list endpoint.
     * If implemented, it should return an array of resource definitions that
     * can be generated from this template.
     *
     * @return array|null Array of resources matching this template, or null if listing is not supported
     */
    public function list(): ?array
    {
        return null; // Default: no listing supported
    }

    /**
     * Read the content of a resource that matches this template.
     *
     * This method is called when a client requests to read a resource
     * whose URI matches this template. The implementation should generate
     * and return the resource content based on the extracted parameters.
     *
     * @param  string  $uri  The full URI being requested
     * @param  array  $params  Parameters extracted from the URI template
     * @return array Resource content array with 'uri', 'mimeType', and either 'text' or 'blob'
     */
    abstract public function read(string $uri, array $params): array;
}
