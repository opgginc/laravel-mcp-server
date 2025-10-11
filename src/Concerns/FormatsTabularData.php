<?php

namespace OPGG\LaravelMcpServer\Concerns;

use JsonSerializable;

/**
 * Helper utilities for converting flat tabular tool output into
 * multiple LLM friendly representations (JSON, CSV, Markdown).
 */
trait FormatsTabularData
{
    /**
     * CSV delimiter character.
     */
    protected string $tabularCsvDelimiter = ',';

    /**
     * CSV enclosure character.
     */
    protected string $tabularCsvEnclosure = '"';

    /**
     * MIME type used for CSV responses.
     */
    protected string $tabularCsvMimeType = 'text/csv';

    /**
     * MIME type used for Markdown responses.
     */
    protected string $tabularMarkdownMimeType = 'text/markdown';

    /**
     * Returns the MIME types registered for tabular helper outputs.
     *
     * @return array<string, string>
     */
    protected function tabularContentFormats(): array
    {
        return [
            'csv' => $this->tabularCsvMimeType,
            'markdown' => $this->tabularMarkdownMimeType,
        ];
    }

    /**
     * Builds additional content payload entries (CSV + Markdown) when a result
     * contains a 1-depth list of associative rows.
     *
     * @param  mixed  $data
     * @return array<int, array{type: string, text: string, mimeType: string}>
     */
    protected function buildTabularContent(mixed $data): array
    {
        $rows = $this->resolveTabularRows($data);

        if ($rows === null) {
            return [];
        }

        return [
            [
                'type' => 'text',
                'text' => $this->convertTabularRowsToCsv($rows),
                'mimeType' => $this->tabularCsvMimeType,
            ],
            [
                'type' => 'text',
                'text' => $this->convertTabularRowsToMarkdown($rows),
                'mimeType' => $this->tabularMarkdownMimeType,
            ],
        ];
    }

    /**
     * Detects and normalises a flat list of tabular rows.
     *
     * @param  mixed  $data
     * @return array<int, array<string, scalar|null>>|null
     */
    protected function resolveTabularRows(mixed $data): ?array
    {
        if (! is_array($data) || $data === []) {
            return null;
        }

        if (! array_is_list($data)) {
            return null;
        }

        $normalizedRows = [];
        $allKeys = [];

        foreach ($data as $row) {
            $rowArray = $this->convertRowToArray($row);

            if ($rowArray === null) {
                return null;
            }

            foreach ($rowArray as $value) {
                if (is_array($value) || is_object($value)) {
                    return null;
                }
            }

            $normalizedRows[] = $rowArray;
            $allKeys = array_merge($allKeys, array_keys($rowArray));
        }

        if ($normalizedRows === []) {
            return null;
        }

        $orderedKeys = array_values(array_unique($allKeys));

        if ($orderedKeys === []) {
            return null;
        }

        return array_map(function (array $row) use ($orderedKeys): array {
            $ordered = [];
            foreach ($orderedKeys as $key) {
                $ordered[$key] = $row[$key] ?? null;
            }

            return $ordered;
        }, $normalizedRows);
    }

    /**
     * Converts an individual row into an associative array.
     *
     * @param  mixed  $row
     * @return array<string, mixed>|null
     */
    protected function convertRowToArray(mixed $row): ?array
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            if ($row instanceof JsonSerializable) {
                $serialized = $row->jsonSerialize();

                if (is_array($serialized)) {
                    return $serialized;
                }
            }

            $vars = get_object_vars($row);

            if (! empty($vars)) {
                return $vars;
            }
        }

        return null;
    }

    /**
     * Converts a list of rows into CSV text.
     *
     * @param  array<int, array<string, scalar|null>>  $rows
     */
    protected function convertTabularRowsToCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            return '';
        }

        $headers = array_keys($rows[0]);
        fputcsv($stream, $headers, $this->tabularCsvDelimiter, $this->tabularCsvEnclosure);

        foreach ($rows as $row) {
            $values = array_map(fn ($value) => $this->stringifyTabularValue($value), $row);
            fputcsv($stream, $values, $this->tabularCsvDelimiter, $this->tabularCsvEnclosure);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        if ($csv === false) {
            return '';
        }

        return rtrim($csv, "\r\n");
    }

    /**
     * Converts a list of rows into a Markdown table string.
     *
     * @param  array<int, array<string, scalar|null>>  $rows
     */
    protected function convertTabularRowsToMarkdown(array $rows): string
    {
        $headers = array_keys($rows[0]);
        $headerLine = '| '.implode(' | ', array_map([$this, 'escapeMarkdownValue'], $headers)).' |';
        $separatorLine = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';

        $lines = [$headerLine, $separatorLine];

        foreach ($rows as $row) {
            $cells = array_map(function ($value) {
                return $this->escapeMarkdownValue($this->stringifyTabularValue($value));
            }, $row);

            $lines[] = '| '.implode(' | ', $cells).' |';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Converts scalar/null values to their string representations.
     */
    protected function stringifyTabularValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '' : $encoded;
    }

    /**
     * Escapes Markdown table control characters.
     */
    protected function escapeMarkdownValue(string $value): string
    {
        $escaped = str_replace('|', '\\|', $value);
        $escaped = str_replace(["\r\n", "\r", "\n"], '<br />', $escaped);

        return $escaped;
    }
}
