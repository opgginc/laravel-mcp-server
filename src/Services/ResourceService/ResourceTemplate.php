<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

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
}
