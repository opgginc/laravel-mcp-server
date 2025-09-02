<?php

namespace OPGG\LaravelMcpServer\Utils;

use InvalidArgumentException;

/**
 * A lightweight JSON Schema validator for MCP tool output validation.
 * Provides basic validation for common schema types without external dependencies.
 */
class JsonSchemaValidator
{
    /**
     * Maximum recursion depth to prevent stack overflow attacks
     */
    private const MAX_RECURSION_DEPTH = 100;

    /**
     * Validates data against a JSON schema.
     *
     * @param  mixed  $data  The data to validate
     * @param  array<string, mixed>  $schema  The JSON schema to validate against
     * @return bool True if the data is valid
     *
     * @throws InvalidArgumentException If validation fails
     */
    public static function validate(mixed $data, array $schema): bool
    {
        try {
            self::validateType($data, $schema, 0);

            return true;
        } catch (InvalidArgumentException $e) {
            throw $e;
        }
    }

    /**
     * Validates the data type and structure.
     *
     * @param  mixed  $data  The data to validate
     * @param  array<string, mixed>  $schema  The JSON schema to validate against
     * @param  int  $depth  Current recursion depth
     *
     * @throws InvalidArgumentException
     */
    private static function validateType(mixed $data, array $schema, int $depth = 0): void
    {
        if ($depth > self::MAX_RECURSION_DEPTH) {
            throw new InvalidArgumentException('Maximum schema nesting depth exceeded');
        }

        $type = $schema['type'] ?? null;

        if ($type === null) {
            return; // No type constraint
        }

        switch ($type) {
            case 'object':
                self::validateObject($data, $schema, $depth);
                break;
            case 'array':
                self::validateArray($data, $schema, $depth);
                break;
            case 'string':
                if (! is_string($data)) {
                    throw new InvalidArgumentException('Expected string, got '.gettype($data));
                }
                break;
            case 'number':
            case 'integer':
                if (! is_numeric($data)) {
                    throw new InvalidArgumentException('Expected number, got '.gettype($data));
                }
                if ($type === 'integer' && ! is_int($data)) {
                    throw new InvalidArgumentException('Expected integer, got '.gettype($data));
                }
                break;
            case 'boolean':
                if (! is_bool($data)) {
                    throw new InvalidArgumentException('Expected boolean, got '.gettype($data));
                }
                break;
            case 'null':
                if ($data !== null) {
                    throw new InvalidArgumentException('Expected null, got '.gettype($data));
                }
                break;
            default:
                throw new InvalidArgumentException("Unsupported type: {$type}");
        }
    }

    /**
     * Validates object structure and properties.
     *
     * @param  mixed  $data  The data to validate
     * @param  array<string, mixed>  $schema  The JSON schema to validate against
     * @param  int  $depth  Current recursion depth
     *
     * @throws InvalidArgumentException
     */
    private static function validateObject(mixed $data, array $schema, int $depth = 0): void
    {
        if (! is_array($data) && ! is_object($data)) {
            throw new InvalidArgumentException('Expected object, got '.gettype($data));
        }

        // Convert to associative array for easier handling
        $dataArray = is_object($data) ? (array) $data : $data;

        // Check required properties
        $required = $schema['required'] ?? [];
        foreach ($required as $property) {
            if (! array_key_exists($property, $dataArray)) {
                throw new InvalidArgumentException("Missing required property: {$property}");
            }
        }

        // Validate properties
        $properties = $schema['properties'] ?? [];
        foreach ($properties as $property => $propertySchema) {
            if (array_key_exists($property, $dataArray)) {
                self::validateType($dataArray[$property], $propertySchema, $depth + 1);
            }
        }

        // Check for additional properties if specified
        if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false) {
            $allowedProperties = array_keys($properties);
            $dataProperties = array_keys($dataArray);
            $extraProperties = array_diff($dataProperties, $allowedProperties);

            if (! empty($extraProperties)) {
                throw new InvalidArgumentException('Additional properties not allowed: '.implode(', ', $extraProperties));
            }
        }
    }

    /**
     * Validates array structure and items.
     *
     * @param  mixed  $data  The data to validate
     * @param  array<string, mixed>  $schema  The JSON schema to validate against
     * @param  int  $depth  Current recursion depth
     *
     * @throws InvalidArgumentException
     */
    private static function validateArray(mixed $data, array $schema, int $depth = 0): void
    {
        if (! is_array($data)) {
            throw new InvalidArgumentException('Expected array, got '.gettype($data));
        }

        // Validate array items if schema is provided
        if (isset($schema['items'])) {
            foreach ($data as $index => $item) {
                try {
                    self::validateType($item, $schema['items'], $depth + 1);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("Array item at index {$index}: ".$e->getMessage());
                }
            }
        }

        // Check minimum/maximum items
        $count = count($data);
        if (isset($schema['minItems']) && $count < $schema['minItems']) {
            throw new InvalidArgumentException("Array must have at least {$schema['minItems']} items, got {$count}");
        }

        if (isset($schema['maxItems']) && $count > $schema['maxItems']) {
            throw new InvalidArgumentException("Array must have at most {$schema['maxItems']} items, got {$count}");
        }
    }
}
