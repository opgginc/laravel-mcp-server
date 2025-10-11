<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Concerns;

use InvalidArgumentException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolResponse;
use Stringable;

/**
 * Helper utilities for converting flat tool data into tabular formats.
 *
 * Tools may opt-in to these helpers by using the trait in their implementation.
 */
trait FormatsTabularToolResponses
{
    /**
     * Default column name used when normalising a list of scalar values.
     */
    protected string $tabularScalarColumnName = 'value';

    /**
     * CSV delimiter used when building CSV strings.
     */
    protected string $csvDelimiter = ',';

    /**
     * CSV enclosure used when building CSV strings.
     */
    protected string $csvEnclosure = '"';

    /**
     * CSV escape character used when building CSV strings.
     */
    protected string $csvEscapeCharacter = '\\';

    /**
     * Create a text-based tool response with a custom MIME type.
     */
    protected function toolTextResponse(string $text, string $type = 'text', array $metadata = []): ToolResponse
    {
        return ToolResponse::text($text, $type, $metadata);
    }

    /**
     * Convert the provided data into a CSV formatted tool response.
     *
     * @param  array<int|string, mixed>  $rows
     * @param  array<int, string>|null  $columns
     */
    protected function toolCsvResponse(array $rows, ?array $columns = null, array $metadata = []): ToolResponse
    {
        return $this->toolTextResponse($this->toCsv($rows, $columns), 'text/csv', $metadata);
    }

    /**
     * Convert the provided data into a Markdown table tool response.
     *
     * @param  array<int|string, mixed>  $rows
     * @param  array<int, string>|null  $columns
     */
    protected function toolMarkdownTableResponse(array $rows, ?array $columns = null, array $metadata = []): ToolResponse
    {
        return $this->toolTextResponse($this->toMarkdownTable($rows, $columns), 'text/markdown', $metadata);
    }

    /**
     * Generate a CSV string from the provided tabular data.
     *
     * @param  array<int|string, mixed>  $rows
     * @param  array<int, string>|null  $columns
     */
    protected function toCsv(array $rows, ?array $columns = null): string
    {
        [$normalisedRows, $resolvedColumns] = $this->normaliseTabularRows($rows, $columns);

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new InvalidArgumentException('Unable to create temporary stream for CSV generation.');
        }

        fputcsv($handle, $resolvedColumns, $this->csvDelimiter, $this->csvEnclosure, $this->csvEscapeCharacter);

        foreach ($normalisedRows as $row) {
            $line = [];
            foreach ($resolvedColumns as $column) {
                $line[] = $row[$column] ?? '';
            }

            fputcsv($handle, $line, $this->csvDelimiter, $this->csvEnclosure, $this->csvEscapeCharacter);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * Generate a Markdown table from the provided tabular data.
     *
     * @param  array<int|string, mixed>  $rows
     * @param  array<int, string>|null  $columns
     */
    protected function toMarkdownTable(array $rows, ?array $columns = null): string
    {
        [$normalisedRows, $resolvedColumns] = $this->normaliseTabularRows($rows, $columns);

        $headerCells = array_map(fn (string $column) => $this->escapeMarkdownCell($column), $resolvedColumns);
        $header = '| '.implode(' | ', $headerCells)." |\n";
        $separator = '| '.implode(' | ', array_fill(0, count($resolvedColumns), '---'))." |\n";

        $body = '';
        foreach ($normalisedRows as $row) {
            $cells = [];
            foreach ($resolvedColumns as $column) {
                $cells[] = $this->escapeMarkdownCell($row[$column] ?? '');
            }

            $body .= '| '.implode(' | ', $cells)." |\n";
        }

        return $header.$separator.$body;
    }

    /**
     * Normalise arbitrary flat data into rows and columns suitable for tabular formats.
     *
     * @param  array<int|string, mixed>  $rows
     * @param  array<int, string>|null  $columns
     * @return array{0: list<array<string, string>>, 1: list<string>}
     */
    private function normaliseTabularRows(array $rows, ?array $columns = null): array
    {
        $listOfRows = [];

        if ($rows === []) {
            $listOfRows = [];
        } elseif (array_is_list($rows) && $rows !== []) {
            $listOfRows = $rows;
        } else {
            $listOfRows = [$rows];
        }

        $resolvedColumns = $columns !== null ? array_values(array_map('strval', $columns)) : [];
        $normalisedRows = [];

        foreach ($listOfRows as $row) {
            if (is_array($row)) {
                $normalisedRow = [];
                foreach ($row as $key => $value) {
                    if (! $this->isTabularScalar($value)) {
                        throw new InvalidArgumentException('Nested arrays or objects cannot be converted to tabular data.');
                    }

                    $column = (string) $key;
                    $normalisedRow[$column] = $this->stringifyTabularValue($value);

                    if ($columns === null && ! in_array($column, $resolvedColumns, true)) {
                        $resolvedColumns[] = $column;
                    }
                }
                $normalisedRows[] = $normalisedRow;
            } elseif ($this->isTabularScalar($row)) {
                $column = $columns[0] ?? $resolvedColumns[0] ?? $this->tabularScalarColumnName;
                if ($columns === null && $resolvedColumns === []) {
                    $resolvedColumns[] = $column;
                }

                $normalisedRows[] = [$column => $this->stringifyTabularValue($row)];
            } else {
                throw new InvalidArgumentException('Tabular conversion requires scalar values or flat associative arrays.');
            }
        }

        if ($columns !== null && $columns === []) {
            throw new InvalidArgumentException('Columns cannot be an empty array.');
        }

        if ($resolvedColumns === []) {
            $resolvedColumns[] = $this->tabularScalarColumnName;
        }

        return [$normalisedRows, $resolvedColumns];
    }

    /**
     * Determine if the provided value can be represented in a table cell.
     */
    private function isTabularScalar(mixed $value): bool
    {
        return $value === null
            || is_scalar($value)
            || $value instanceof Stringable;
    }

    /**
     * Convert scalar-like values into their string representation.
     */
    private function stringifyTabularValue(mixed $value): string
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Escape Markdown control characters for table cells.
     */
    private function escapeMarkdownCell(string $value): string
    {
        $escaped = str_replace('|', '\\|', $value);
        $escaped = str_replace(["\r\n", "\r", "\n"], '<br />', $escaped);

        return $escaped;
    }
}
