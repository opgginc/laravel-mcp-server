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
     * @param  array<int, array{type: string, text: string, source?: string}>  $content
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(private array $content, private array $metadata = [])
    {
        if (array_key_exists('content', $metadata)) {
            throw new InvalidArgumentException('Metadata must not contain a content key.');
        }

        $this->content = array_values($content);

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
     * Create a ToolResponse that includes structured content alongside serialised text.
     *
     * @param  array<int, array{type: string, text: string, source?: string}>|null  $content
     * @param  array<string, mixed>  $metadata
     *
     * @throws JsonException
     */
    public static function structured(array $structuredContent, ?array $content = null, array $metadata = []): self
    {
        $json = json_encode($structuredContent, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $contentItems = $content !== null ? array_values($content) : [];

        $hasSerialisedText = false;
        foreach ($contentItems as $item) {
            if (isset($item['type'], $item['text']) && $item['type'] === 'text' && $item['text'] === $json) {
                $hasSerialisedText = true;
                break;
            }
        }

        if (! $hasSerialisedText) {
            $contentItems[] = [
                'type' => 'text',
                'text' => $json,
            ];
        }

        return new self($contentItems, [
            ...$metadata,
            // The MCP 2025-06-18 spec encourages servers to mirror structured payloads in the
            // `structuredContent` field for reliable client parsing.
            // @see https://modelcontextprotocol.io/specification/2025-06-18#structured-content
            'structuredContent' => $structuredContent,
        ]);
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
        return [
            ...$this->metadata,
            'content' => $this->content,
        ];
    }
}
