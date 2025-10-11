<?php

namespace OPGG\LaravelMcpServer\Utils;

use DateTimeInterface;
use InvalidArgumentException;

class TabularDataFormatter
{
    public const META_KEY = '__mcp_tabular';

    /**
     * Determine if the supplied rows represent flat tabular data.
     */
    public static function isTabular(array $rows, ?array $headers = null): bool
    {
        if ($headers !== null) {
            foreach ($headers as $header) {
                if (! is_string($header)) {
                    return false;
                }
            }
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($row as $value) {
                if (is_array($value) || is_object($value)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Resolve headers from provided arguments or derive them from the first row.
     */
    public static function resolveHeaders(array $rows, ?array $headers = null): array
    {
        if ($headers !== null) {
            return array_map(static fn ($header) => (string) $header, array_values($headers));
        }

        $firstRow = $rows[0] ?? null;
        if ($firstRow === null) {
            return [];
        }

        if (! is_array($firstRow)) {
            throw new InvalidArgumentException('Tabular rows must be arrays.');
        }

        if (array_is_list($firstRow)) {
            return array_map(static fn (int $index) => 'column_'.($index + 1), array_keys($firstRow));
        }

        return array_map(static fn ($header) => (string) $header, array_keys($firstRow));
    }

    /**
     * Normalize rows to contain values for every header and ensure scalar output.
     */
    public static function normalizeRows(array $rows, array $headers): array
    {
        return array_map(static function (array $row) use ($headers): array {
            $normalized = [];
            foreach ($headers as $header) {
                $normalized[$header] = self::stringifyValue($row[$header] ?? null);
            }

            return $normalized;
        }, $rows);
    }

    /**
     * Convert normalized rows to a CSV string.
     */
    public static function toCsv(array $rows, array $headers, string $delimiter = ','): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new InvalidArgumentException('Unable to open temporary stream for CSV generation.');
        }

        if ($headers !== []) {
            fputcsv($stream, $headers, $delimiter);
        }

        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), $delimiter);
        }

        rewind($stream);

        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $csv;
    }

    /**
     * Convert normalized rows to a Markdown table.
     */
    public static function toMarkdown(array $rows, array $headers): string
    {
        if ($headers === []) {
            return '';
        }

        $lines = [];
        $lines[] = '| '.implode(' | ', array_map([self::class, 'escapeMarkdownCell'], $headers)).' |';
        $lines[] = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';

        foreach ($rows as $row) {
            $cells = [];
            foreach ($headers as $header) {
                $cells[] = self::escapeMarkdownCell($row[$header] ?? '');
            }

            $lines[] = '| '.implode(' | ', $cells).' |';
        }

        return implode(PHP_EOL, $lines);
    }

    protected static function escapeMarkdownCell(string $value): string
    {
        $escaped = str_replace(['|', PHP_EOL, "\r"], ['\\|', '<br>', ''], $value);

        return trim($escaped);
    }

    protected static function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }
}
