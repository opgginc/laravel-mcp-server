<?php

use OPGG\LaravelMcpServer\Contracts\JsonSchema\JsonSchema as JsonSchemaContract;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchemaTypeFactory;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;

test('json schema type factory implements package json schema contract', function () {
    expect(new JsonSchemaTypeFactory)->toBeInstanceOf(JsonSchemaContract::class);
});

test('json schema builder serializes object schema like laravel style', function () {
    $schema = JsonSchema::object([
        'name' => JsonSchema::string()->description('Developer name')->required(),
        'platform' => JsonSchema::string()->enum(Platform::class)->default('web'),
        'tags' => JsonSchema::array()->items(JsonSchema::string())->min(1),
    ])->withoutAdditionalProperties()->toArray();

    expect($schema['type'])->toBe('object');
    expect($schema['properties']['name']['type'])->toBe('string');
    expect($schema['properties']['name']['description'])->toBe('Developer name');
    expect($schema['required'])->toContain('name');
    expect($schema['properties']['platform']['enum'])->toBe(['web', 'desktop']);
    expect($schema['properties']['tags']['items']['type'])->toBe('string');
    expect($schema['additionalProperties'])->toBeFalse();
});

test('json schema builder compact removes enum and appends default examples hint', function () {
    $schema = JsonSchema::object([
        'platform' => JsonSchema::string()
            ->description('Client platform')
            ->enum(['web', 'desktop', 'mobile', 'console'])
            ->compact(),
    ])->toArray();

    $platform = $schema['properties']['platform'];

    expect($platform)->not->toHaveKey('enum');
    expect($platform['description'])->toBe('Client platform Examples: web, desktop, mobile');
});

test('json schema builder compact supports custom string message', function () {
    $schema = JsonSchema::object([
        'platform' => JsonSchema::string()
            ->description('Client platform')
            ->enum(Platform::class)
            ->compact('Use UPPER_SNAKE_CASE champion key.'),
    ])->toArray();

    expect($schema['properties']['platform'])->not->toHaveKey('enum');
    expect($schema['properties']['platform']['description'])->toBe('Client platform Use UPPER_SNAKE_CASE champion key.');
});

test('json schema builder compact supports int example count and null default count', function () {
    $intSchema = JsonSchema::object([
        'platform' => JsonSchema::string()
            ->description('Client platform')
            ->enum(Platform::class)
            ->compact(1),
    ])->toArray();

    $nullSchema = JsonSchema::object([
        'platform' => JsonSchema::string()
            ->description('Client platform')
            ->enum(Platform::class)
            ->compact(null),
    ])->toArray();

    expect($intSchema['properties']['platform'])->not->toHaveKey('enum');
    expect($intSchema['properties']['platform']['description'])->toBe('Client platform Examples: web');
    expect($nullSchema['properties']['platform'])->not->toHaveKey('enum');
    expect($nullSchema['properties']['platform']['description'])->toBe('Client platform Examples: web, desktop');
});
