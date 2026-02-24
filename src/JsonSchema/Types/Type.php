<?php

namespace OPGG\LaravelMcpServer\JsonSchema\Types;

use InvalidArgumentException;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\JsonSchema\Serializer;
use ReflectionEnum;
use ReflectionEnumBackedCase;

abstract class Type extends JsonSchema
{
    /**
     * Default number of enum examples when compact mode is enabled with no explicit count.
     */
    protected const DEFAULT_COMPACT_ENUM_EXAMPLE_COUNT = 3;

    /**
     * Whether the type is required.
     */
    protected ?bool $required = null;

    /**
     * The type's title.
     */
    protected ?string $title = null;

    /**
     * The type's description.
     */
    protected ?string $description = null;

    /**
     * The default value for the type.
     */
    protected mixed $default = null;

    /**
     * The set of allowed values for the type.
     *
     * @var array<int, mixed>|null
     */
    protected ?array $enum = null;

    /**
     * Indicates if the type is nullable.
     */
    protected ?bool $nullable = null;

    /**
     * Indicates whether enum values should be compacted into description hints.
     */
    protected ?bool $compact = null;

    /**
     * Compact mode option:
     * - string: custom suffix message
     * - int: number of enum examples to include
     * - null: use default number of examples
     */
    protected string|int|null $compactValue = null;

    /**
     * Indicate that the type is required.
     */
    public function required(bool $required = true): static
    {
        if ($required) {
            $this->required = true;
        }

        return $this;
    }

    /**
     * Indicate that the type is optional.
     */
    public function nullable(bool $nullable = true): static
    {
        if ($nullable) {
            $this->nullable = true;
        }

        return $this;
    }

    /**
     * Compact enum schema by removing `enum` and appending a hint to description.
     *
     * Behavior:
     * - compact() / compact(null): append e.g. hint with default example count.
     * - compact(int $n): append e.g. hint with first $n enum values.
     * - compact(string $message): append custom message.
     */
    public function compact(string|int|null $value = null): static
    {
        $this->compact = true;
        $this->compactValue = $value;

        return $this;
    }

    /**
     * Set the type's title.
     */
    public function title(string $value): static
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Set the type's description.
     */
    public function description(string $value): static
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Restrict the value to one of the provided enumerated values.
     *
     * @param  class-string|array<int, mixed>  $values
     *
     * @throws \InvalidArgumentException
     */
    public function enum(array|string $values): static
    {
        if (is_string($values)) {
            $values = $this->resolveBackedEnumValues($values);
        }

        // Keep order and allow complex values (arrays / objects) without forcing uniqueness...
        $this->enum = array_values($values);

        return $this;
    }

    /**
     * Resolve values from a backed enum class-string.
     *
     * @param  class-string  $enumClass
     * @return array<int, int|string>
     *
     * @throws \InvalidArgumentException
     */
    private function resolveBackedEnumValues(string $enumClass): array
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException('The provided class must be a BackedEnum.');
        }

        $reflection = new ReflectionEnum($enumClass);

        if (! $reflection->isBacked()) {
            throw new InvalidArgumentException('The provided class must be a BackedEnum.');
        }

        $values = [];

        foreach ($reflection->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase) {
                $values[] = $case->getBackingValue();
            }
        }

        return $values;
    }

    /**
     * Convert the type to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Serializer::serialize($this);
    }

    /**
     * Convert the type to its string representation.
     */
    public function toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * Convert the type to its string representation.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Get the default enum example count for compact mode.
     */
    public static function defaultCompactEnumExampleCount(): int
    {
        return self::DEFAULT_COMPACT_ENUM_EXAMPLE_COUNT;
    }
}
