<?php

namespace OPGG\LaravelMcpServer\Services\ResourceService;

/**
 * Base Resource class representing a single MCP resource.
 */
abstract class Resource
{
    /**
     * Unique URI identifying the resource.
     */
    public string $uri;

    /**
     * Human readable name of the resource.
     */
    public string $name;

    /**
     * Optional title for UI display.
     */
    public ?string $title = null;

    /**
     * Optional description explaining the resource.
     */
    public ?string $description = null;

    /**
     * Optional icon metadata entries.
     *
     * @var array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>
     */
    public array $icons = [];

    /**
     * Optional annotations for MCP clients.
     *
     * @var array<string, mixed>
     */
    public array $annotations = [];

    /**
     * Optional transport-level metadata.
     *
     * @var array<string, mixed>
     */
    public array $meta = [];

    /**
     * Optional MIME type hint.
     */
    public ?string $mimeType = null;

    /**
     * Optional size information in bytes.
     */
    public ?int $size = null;

    /**
     * Convert the resource definition to an array for the resources/list
     * endpoint.
     */
    public function toArray(): array
    {
        return array_filter([
            'uri' => $this->uri,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'mimeType' => $this->mimeType,
            'size' => $this->size,
            'annotations' => $this->annotations === [] ? null : $this->annotations,
            '_meta' => $this->meta === [] ? null : $this->meta,
            'icons' => $this->icons === [] ? null : array_values($this->icons),
        ], static fn ($v) => $v !== null);
    }

    /**
     * Read the content of the resource. Implementations should return an
     * associative array containing the URI, optional mimeType and one of
     * 'text' (for UTF-8 text) or 'blob' (for base64 encoded binary data).
     */
    abstract public function read(): array;
}
