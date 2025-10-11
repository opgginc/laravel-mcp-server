<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Concerns;

use OPGG\LaravelMcpServer\Utils\TabularDataFormatter;

/**
 * Helper trait for MCP tools that need to expose flat tabular data.
 *
 * This trait centralises helper methods and configuration flags so tools can
 * easily opt-in to CSV/Markdown conversions without re-implementing the
 * formatting logic.
 */
trait ProvidesTabularResponses
{
    /**
     * Array key added to tool responses to signal the presence of tabular data.
     */
    protected string $tabularMetaKey = TabularDataFormatter::META_KEY;

    /**
     * Default delimiter used when creating CSV output.
     */
    protected string $tabularCsvDelimiter = ',';

    /**
     * Controls whether a Markdown table should be generated alongside the CSV.
     */
    protected bool $tabularIncludeMarkdownTable = true;

    /**
     * Attach tabular metadata to an existing response payload.
     */
    protected function withTabularResponse(
        array $response,
        array $rows,
        ?array $headers = null,
        ?string $delimiter = null,
        ?bool $includeMarkdown = null,
    ): array {
        return array_merge($response, $this->tabularMeta($rows, $headers, $delimiter, $includeMarkdown));
    }

    /**
     * Build only the tabular metadata array so it can be merged manually when required.
     */
    protected function tabularMeta(
        array $rows,
        ?array $headers = null,
        ?string $delimiter = null,
        ?bool $includeMarkdown = null,
    ): array {
        return [
            $this->tabularMetaKey => [
                'rows' => $rows,
                'headers' => $headers,
                'delimiter' => $delimiter ?? $this->tabularCsvDelimiter,
                'include_markdown' => $includeMarkdown ?? $this->tabularIncludeMarkdownTable,
            ],
        ];
    }

    /**
     * Convert the provided rows to a CSV string using the configured delimiter.
     */
    protected function tabularToCsv(array $rows, ?array $headers = null, ?string $delimiter = null): string
    {
        $resolvedHeaders = TabularDataFormatter::resolveHeaders($rows, $headers);
        $normalizedRows = TabularDataFormatter::normalizeRows($rows, $resolvedHeaders);

        return TabularDataFormatter::toCsv($normalizedRows, $resolvedHeaders, $delimiter ?? $this->tabularCsvDelimiter);
    }

    /**
     * Convert the provided rows to a Markdown table string.
     */
    protected function tabularToMarkdown(array $rows, ?array $headers = null): string
    {
        $resolvedHeaders = TabularDataFormatter::resolveHeaders($rows, $headers);
        $normalizedRows = TabularDataFormatter::normalizeRows($rows, $resolvedHeaders);

        return TabularDataFormatter::toMarkdown($normalizedRows, $resolvedHeaders);
    }
}
