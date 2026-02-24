<?php

namespace OPGG\LaravelMcpServer\Utils;

use ReflectionEnum;
use ReflectionEnumBackedCase;

class JsonSchemaEnumUtil
{
    /**
     * Normalize enum class references in schema arrays.
     *
     * Example:
     * - ['enum' => SomeBackedEnum::class] => ['enum' => ['value1', 'value2']]
     * - ['enum' => SomeUnitEnum::class] => ['enum' => ['CASE_A', 'CASE_B']]
     */
    public static function normalizeEnumClassReferences(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(static fn ($item) => self::normalizeEnumClassReferences($item), $value);
        }

        $normalized = [];
        foreach ($value as $key => $childValue) {
            if ($key === 'enum') {
                $normalized[$key] = self::normalizeEnumValue($childValue);

                continue;
            }

            $normalized[$key] = self::normalizeEnumClassReferences($childValue);
        }

        return $normalized;
    }

    private static function normalizeEnumValue(mixed $value): mixed
    {
        if (! is_string($value) || ! enum_exists($value)) {
            return $value;
        }

        $reflection = new ReflectionEnum($value);
        $enumValues = [];

        foreach ($reflection->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase) {
                $enumValues[] = $case->getBackingValue();

                continue;
            }

            $enumValues[] = $case->getName();
        }

        return $enumValues;
    }
}
