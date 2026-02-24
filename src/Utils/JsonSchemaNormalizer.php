<?php

namespace OPGG\LaravelMcpServer\Utils;

use OPGG\LaravelMcpServer\JsonSchema\Serializer;
use OPGG\LaravelMcpServer\JsonSchema\Types\ObjectType;
use OPGG\LaravelMcpServer\JsonSchema\Types\Type;

class JsonSchemaNormalizer
{
    /**
     * Keys that indicate a fully-formed JSON Schema root object.
     *
     * @var array<int, string>
     */
    private const ROOT_SCHEMA_KEYS = [
        '$schema',
        '$id',
        '$ref',
        'type',
        'properties',
        'required',
        'items',
        'oneOf',
        'anyOf',
        'allOf',
        'additionalProperties',
        'title',
        'description',
        'default',
        'enum',
        'nullable',
    ];

    /**
     * Normalize tool schema arrays into transport-ready JSON Schema arrays.
     *
     * Supports:
     * - Full JSON Schema arrays (returned as-is, with enum class expansion)
     * - Property maps with JsonSchema Type objects:
     *   ['name' => JsonSchema::string()->required()]
     */
    public static function normalize(array $schema, ?int $compactEnumExampleCount = null): array
    {
        if ($schema === []) {
            return [];
        }

        if (self::isTypeObjectMap($schema)) {
            /** @var array<string, Type> $schema */
            $schema = Serializer::serialize(new ObjectType($schema), $compactEnumExampleCount);

            $schema = JsonSchemaEnumUtil::normalizeEnumClassReferences($schema);

            return is_array($schema) ? $schema : [];
        }

        $normalized = self::normalizeValue($schema, $compactEnumExampleCount);
        if (! is_array($normalized)) {
            return [];
        }

        if (self::isPropertyMap($normalized)) {
            $normalized = [
                'type' => 'object',
                'properties' => $normalized,
            ];
        }

        $normalized = JsonSchemaEnumUtil::normalizeEnumClassReferences($normalized);

        return is_array($normalized) ? $normalized : [];
    }

    private static function normalizeValue(mixed $value, ?int $compactEnumExampleCount): mixed
    {
        if ($value instanceof Type) {
            return Serializer::serialize($value, $compactEnumExampleCount);
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                static fn ($item) => self::normalizeValue($item, $compactEnumExampleCount),
                $value
            );
        }

        $normalized = [];
        foreach ($value as $key => $childValue) {
            $normalized[$key] = self::normalizeValue($childValue, $compactEnumExampleCount);
        }

        return $normalized;
    }

    /**
     * @param  array<array-key, mixed>  $schema
     */
    private static function isTypeObjectMap(array $schema): bool
    {
        if ($schema === [] || array_is_list($schema)) {
            return false;
        }

        foreach ($schema as $key => $propertySchema) {
            if (! is_string($key)) {
                return false;
            }

            if (in_array($key, self::ROOT_SCHEMA_KEYS, true)) {
                return false;
            }

            if (! $propertySchema instanceof Type) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if a schema array is a property map instead of a full schema root.
     *
     * @param  array<array-key, mixed>  $schema
     */
    private static function isPropertyMap(array $schema): bool
    {
        if ($schema === [] || array_is_list($schema)) {
            return false;
        }

        foreach ($schema as $key => $propertySchema) {
            if (! is_string($key)) {
                return false;
            }

            if (in_array($key, self::ROOT_SCHEMA_KEYS, true)) {
                return false;
            }

            if (! is_array($propertySchema)) {
                return false;
            }
        }

        return true;
    }
}
