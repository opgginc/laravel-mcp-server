<?php

namespace OPGG\LaravelMcpServer\Utils;

/**
 * Utility class for parsing and matching URI templates according to RFC 6570.
 * Provides basic URI template functionality for MCP resource templates.
 */
class UriTemplateUtil
{
    /**
     * Parse a URI template and extract parameter names.
     *
     * @param  string  $template  URI template (e.g., "database://users/{id}")
     * @return array Array of parameter names
     */
    public static function extractParameters(string $template): array
    {
        preg_match_all('/\{([^}]+)\}/', $template, $matches);

        return $matches[1];
    }

    /**
     * Check if a URI matches a template and extract parameter values.
     *
     * @param  string  $template  URI template (e.g., "database://users/{id}")
     * @param  string  $uri  Actual URI (e.g., "database://users/123")
     * @return array|null Array of parameter values if match, null if no match
     */
    public static function matchUri(string $template, string $uri): ?array
    {
        // Convert template to regex pattern
        $pattern = self::templateToRegex($template);

        if (! preg_match($pattern, $uri, $matches)) {
            return null;
        }

        // Extract parameter names and values
        $paramNames = self::extractParameters($template);
        $paramValues = [];

        for ($i = 0; $i < count($paramNames); $i++) {
            $paramValues[$paramNames[$i]] = $matches[$i + 1] ?? null;
        }

        return $paramValues;
    }

    /**
     * Convert a URI template to a regex pattern.
     *
     * @param  string  $template  URI template
     * @return string Regex pattern
     */
    private static function templateToRegex(string $template): string
    {
        // Build the regex pattern by replacing placeholders with capture groups
        $pattern = $template;

        // First, escape regex special characters that should be literal
        $pattern = preg_replace('/[.^$*+?()\[\]{}|\\\\\/]/', '\\\\$0', $pattern);

        // Then replace our escaped placeholders with capture groups
        $pattern = preg_replace('/\\\\\{[^}]+\\\\\}/', '([^\\\\/]+)', $pattern);

        return '/^'.$pattern.'$/';
    }

    /**
     * Expand a URI template with given parameters.
     *
     * @param  string  $template  URI template
     * @param  array  $params  Parameter values
     * @return string Expanded URI
     */
    public static function expandTemplate(string $template, array $params): string
    {
        $uri = $template;

        foreach ($params as $name => $value) {
            $uri = str_replace('{'.$name.'}', (string) $value, $uri);
        }

        return $uri;
    }

    /**
     * Validate that a URI template is well-formed.
     *
     * @param  string  $template  URI template
     * @return bool True if valid, false otherwise
     */
    public static function isValidTemplate(string $template): bool
    {
        // Check for balanced braces
        $openBraces = substr_count($template, '{');
        $closeBraces = substr_count($template, '}');

        if ($openBraces !== $closeBraces) {
            return false;
        }

        // Check for valid parameter syntax
        return preg_match('/\{[^{}]+\}/', $template) || $openBraces === 0;
    }
}
