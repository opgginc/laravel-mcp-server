<?php

namespace OPGG\LaravelMcpServer\JsonSchema;

use RuntimeException;

class Serializer
{
    /**
     * The properties to ignore when serializing.
     *
     * @var array<int, string>
     */
    protected static array $ignore = ['required', 'nullable', 'compact', 'compactValue'];

    /**
     * Serialize the given property to an array.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public static function serialize(Types\Type $type, ?int $compactEnumExampleCount = null): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = (fn () => get_object_vars($type))->call($type);
        $compact = ($attributes['compact'] ?? false) === true;
        $compactValue = $attributes['compactValue'] ?? null;

        $attributes['type'] = match (get_class($type)) {
            Types\ArrayType::class => 'array',
            Types\BooleanType::class => 'boolean',
            Types\IntegerType::class => 'integer',
            Types\NumberType::class => 'number',
            Types\ObjectType::class => 'object',
            Types\StringType::class => 'string',
            default => throw new RuntimeException('Unsupported ['.get_class($type).'] type.'),
        };

        $nullable = static::isNullable($type);

        if ($nullable) {
            $attributes['type'] = [$attributes['type'], 'null'];
        }

        $attributes = array_filter($attributes, static function (mixed $value, string $key) {
            if (in_array($key, static::$ignore, true)) {
                return false;
            }

            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);

        if ($type instanceof Types\ObjectType) {
            if (count($attributes['properties']) === 0) {
                unset($attributes['properties']);
            } else {
                $required = array_keys(array_filter(
                    $attributes['properties'],
                    static fn (Types\Type $property) => static::isRequired($property),
                ));

                if (count($required) > 0) {
                    $attributes['required'] = $required;
                }

                $attributes['properties'] = array_map(
                    static fn (Types\Type $property) => static::serialize($property, $compactEnumExampleCount),
                    $attributes['properties'],
                );
            }
        }

        if ($type instanceof Types\ArrayType) {
            if (isset($attributes['items']) && $attributes['items'] instanceof Types\Type) {
                $attributes['items'] = static::serialize($attributes['items'], $compactEnumExampleCount);
            }
        }

        if ($compact) {
            $attributes = static::compactEnum($attributes, $compactValue, $compactEnumExampleCount);
        }

        return $attributes;
    }

    /**
     * Remove enum values and append compact hint to description.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected static function compactEnum(array $attributes, mixed $value, ?int $compactEnumExampleCount = null): array
    {
        if (! isset($attributes['enum']) || ! is_array($attributes['enum'])) {
            return $attributes;
        }

        /** @var array<int, mixed> $enumValues */
        $enumValues = $attributes['enum'];
        unset($attributes['enum']);

        $suffix = static::compactDescriptionSuffix($enumValues, $value, $compactEnumExampleCount);
        if ($suffix === '') {
            return $attributes;
        }

        $description = $attributes['description'] ?? null;
        if (is_string($description) && $description !== '') {
            $attributes['description'] = rtrim($description).$suffix;

            return $attributes;
        }

        $attributes['description'] = ltrim($suffix);

        return $attributes;
    }

    /**
     * Build compact description suffix from enum values and compact option.
     *
     * @param  array<int, mixed>  $enumValues
     */
    protected static function compactDescriptionSuffix(array $enumValues, mixed $value, ?int $compactEnumExampleCount = null): string
    {
        if (is_string($value)) {
            $customMessage = trim($value);

            return $customMessage !== '' ? ' '.$customMessage : '';
        }

        $limit = max(1, $compactEnumExampleCount ?? Types\Type::defaultCompactEnumExampleCount());
        if (is_int($value) && $value > 0) {
            $limit = $value;
        }

        if ($enumValues === []) {
            return '';
        }

        $sampleValues = array_slice($enumValues, 0, $limit);
        $sampleValues = array_map(
            static function (mixed $sample): string {
                if (is_string($sample) || is_int($sample) || is_float($sample)) {
                    return (string) $sample;
                }

                if (is_bool($sample)) {
                    return $sample ? 'true' : 'false';
                }

                if ($sample === null) {
                    return 'null';
                }

                if (is_array($sample)) {
                    $encoded = json_encode($sample, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    return is_string($encoded) ? $encoded : '';
                }

                return '';
            },
            $sampleValues,
        );

        $sampleValues = array_values(array_filter($sampleValues, static fn (string $sample): bool => $sample !== ''));
        if ($sampleValues === []) {
            return '';
        }

        return ' Examples: '.implode(', ', $sampleValues);
    }

    /**
     * Determine if the given type is required.
     */
    protected static function isRequired(Types\Type $type): bool
    {
        $attributes = (fn () => get_object_vars($type))->call($type);

        return isset($attributes['required']) && $attributes['required'] === true;
    }

    /**
     * Determine if the given type is nullable.
     */
    protected static function isNullable(Types\Type $type): bool
    {
        $attributes = (fn () => get_object_vars($type))->call($type);

        return isset($attributes['nullable']) && $attributes['nullable'] === true;
    }
}
