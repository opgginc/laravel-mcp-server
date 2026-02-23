<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use InvalidArgumentException;
use JsonException;

/**
 * Value object describing a structured tool response.
 */
final class ToolResponse
{
    /**
     * @var array<int, array{type: string, text: string, source?: string}>
     */
    private array $content;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param  array<int, array{type: string, text: string, source?: string}>  $content
     * @param  array<string, mixed>  $metadata
     */
    private bool $includeContent;

    private function __construct(array $content, array $metadata = [], bool $includeContent = true)
    {
        if (array_key_exists('content', $metadata)) {
            throw new InvalidArgumentException('Metadata must not contain a content key.');
        }

        $this->content = array_values($content);
        $this->metadata = $metadata;
        $this->includeContent = $includeContent && $this->content !== [];

        foreach ($this->content as $index => $item) {
            if (! is_array($item) || ! isset($item['type'], $item['text'])) {
                throw new InvalidArgumentException('Each content item must contain type and text keys.');
            }

            if (! is_string($item['type']) || ! is_string($item['text'])) {
                throw new InvalidArgumentException('Content type and text must be strings.');
            }
        }
    }

    /**
     * Create a ToolResponse from raw content data.
     *
     * @param  array<int, array{type: string, text: string, source?: string}>  $content
     * @param  array<string, mixed>  $metadata
     */
    public static function make(array $content, array $metadata = []): self
    {
        return new self($content, $metadata);
    }

    /**
     * Create a ToolResponse for a single text payload.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function text(string $text, string $type = 'text', array $metadata = []): self
    {
        return new self([
            [
                'type' => $type,
                'text' => $text,
            ],
        ], $metadata);
    }

    /**
     * Create a ToolResponse that includes structured content alongside optional serialised text.
     *
     * @param  array<int, array{type: string, text: string, source?: string}>|null  $content
     * @param  array<string, mixed>  $metadata
     */
    public static function structured(array $structuredContent, ?array $content = null, array $metadata = []): self
    {
        $contentItems = $content !== null
            ? array_values($content)
            : [[
                'type' => 'text',
                'text' => self::encodeStructuredContent($structuredContent),
            ]];

        return new self(
            $contentItems,
            [
                ...$metadata,
                // The MCP 2025-11-25 schema encourages servers to mirror structured payloads in the
                // `structuredContent` field for reliable client parsing.
                // @see https://modelcontextprotocol.io/specification/2025-11-25/schema
                'structuredContent' => $structuredContent,
            ]
        );
    }

    private static function encodeStructuredContent(array $structuredContent): string
    {
        try {
            return json_encode($structuredContent, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Failed to encode structuredContent: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Access the raw content payload.
     *
     * @return array<int, array{type: string, text: string, source?: string}>
     */
    public function content(): array
    {
        return $this->content;
    }

    /**
     * Access metadata associated with the response.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /**
     * Convert the response into the array structure expected by the transport.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            ...$this->metadata,
        ];

        if ($this->includeContent) {
            $payload['content'] = $this->content;
        }

        return $payload;
    }
}
