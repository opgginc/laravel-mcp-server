<?php

namespace OPGG\LaravelMcpServer\Contracts\JsonSchema;

use Closure;

interface JsonSchema
{
    /**
     * Create a new object schema instance.
     *
     * @param  (Closure(JsonSchema): array<string, \OPGG\LaravelMcpServer\JsonSchema\Types\Type>)|array<string, \OPGG\LaravelMcpServer\JsonSchema\Types\Type>  $properties
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\ObjectType
     */
    public function object(Closure|array $properties = []);

    /**
     * Create a new array property instance.
     *
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\ArrayType
     */
    public function array();

    /**
     * Create a new string property instance.
     *
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\StringType
     */
    public function string();

    /**
     * Create a new integer property instance.
     *
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\IntegerType
     */
    public function integer();

    /**
     * Create a new number property instance.
     *
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\NumberType
     */
    public function number();

    /**
     * Create a new boolean property instance.
     *
     * @return \OPGG\LaravelMcpServer\JsonSchema\Types\BooleanType
     */
    public function boolean();
}
