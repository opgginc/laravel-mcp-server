<?php

namespace OPGG\LaravelMcpServer\Contracts\JsonSchema;

use Closure;
use OPGG\LaravelMcpServer\JsonSchema\Types\ArrayType;
use OPGG\LaravelMcpServer\JsonSchema\Types\BooleanType;
use OPGG\LaravelMcpServer\JsonSchema\Types\IntegerType;
use OPGG\LaravelMcpServer\JsonSchema\Types\NumberType;
use OPGG\LaravelMcpServer\JsonSchema\Types\ObjectType;
use OPGG\LaravelMcpServer\JsonSchema\Types\StringType;
use OPGG\LaravelMcpServer\JsonSchema\Types\Type;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, Type>)|array<string, Type>  $properties
     * @return ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return BooleanType
     */
    public function boolean();
}
