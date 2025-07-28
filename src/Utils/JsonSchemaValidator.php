<?php

namespace OPGG\LaravelMcpServer\Utils;

use InvalidArgumentException;

/**
 * Simple JSON Schema validator for MCP tool output validation.
 * Provides basic validation for common JSON schema types and structures.
 */
class JsonSchemaValidator
{
    /**
     * Validates data against a JSON schema.
     *
     * @param  mixed  $data  The data to validate
     * @param  array  $schema  The JSON schema to validate against
     * @return bool True if valid, false otherwise
     *
     * @throws InvalidArgumentException If validation fails with detailed error
     */
    public static function validate(mixed $data, array $schema): bool
    {
        try {
            self::validateType($data, $schema);

            return true;
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Validates the type of data according to schema.
     *
     * @param  mixed  $data  The data to validate
     * @param  array  $schema  The schema definition
     * @return void
     *
     * @throws InvalidArgumentException If validation fails
     */
    private static function validateType(mixed $data, array $schema): void
    {
        $type = $schema['type'] ?? null;

        if ($type === null) {
            return; // No type constraint
        }

        switch ($type) {
            case 'object':
                self::validateObject($data, $schema);
                break;
            case 'array':
                self::validateArray($data, $schema);
                break;
            case 'string':
                if (! is_string($data)) {
                    throw new InvalidArgumentException("Expected string, got ".gettype($data));
                }
                break;
            case 'number':
                if (! is_numeric($data)) {
                    throw new InvalidArgumentException("Expected number, got ".gettype($data));
                }
                break;
            case 'integer':
                if (! is_int($data)) {
                    throw new InvalidArgumentException("Expected integer, got ".gettype($data));
                }
                break;
            case 'boolean':
                if (! is_bool($data)) {
                    throw new InvalidArgumentException("Expected boolean, got ".gettype($data));
                }
                break;
            case 'null':
                if ($data !== null) {
                    throw new InvalidArgumentException("Expected null, got ".gettype($data));
                }
                break;
            default:
                throw new InvalidArgumentException("Unsupported schema type: {$type}");
        }
    }

    /**
     * Validates object data against object schema.
     *
     * @param  mixed  $data  The data to validate
     * @param  array  $schema  The object schema
     * @return void
     *
     * @throws InvalidArgumentException If validation fails
     */
    private static function validateObject(mixed $data, array $schema): void
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException("Expected object/array, got ".gettype($data));
        }

        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        // Check required properties
        foreach ($required as $requiredProp) {
            if (! array_key_exists($requiredProp, $data)) {
                throw new InvalidArgumentException("Missing required property: {$requiredProp}");
            }
        }

        // Validate each property
        foreach ($data as $key => $value) {
            if (isset($properties[$key])) {
                try {
                    self::validateType($value, $properties[$key]);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("Property '{$key}': ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Validates array data against array schema.
     *
     * @param  mixed  $data  The data to validate
     * @param  array  $schema  The array schema
     * @return void
     *
     * @throws InvalidArgumentException If validation fails
     */
    private static function validateArray(mixed $data, array $schema): void
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException("Expected array, got ".gettype($data));
        }

        $itemsSchema = $schema['items'] ?? null;
        if ($itemsSchema === null) {
            return; // No items constraint
        }

        foreach ($data as $index => $item) {
            try {
                self::validateType($item, $itemsSchema);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("Array item at index {$index}: ".$e->getMessage());
            }
        }
    }

    /**
     * Validates data against schema and returns validation result with error message.
     *
     * @param  mixed  $data  The data to validate
     * @param  array  $schema  The JSON schema to validate against
     * @return array{valid: bool, error?: string} Validation result
     */
    public static function validateWithResult(mixed $data, array $schema): array
    {
        try {
            self::validate($data, $schema);

            return ['valid' => true];
        } catch (InvalidArgumentException $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}