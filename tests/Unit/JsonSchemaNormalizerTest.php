<?php

use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;
use OPGG\LaravelMcpServer\Utils\JsonSchemaNormalizer;

test('json schema normalizer wraps property type maps into object schema', function () {
    $schema = [
        'name' => JsonSchema::string()->required(),
        'platform' => JsonSchema::string()->enum(Platform::class),
    ];

    $normalized = JsonSchemaNormalizer::normalize($schema);

    expect($normalized['type'])->toBe('object');
    expect($normalized['required'])->toBe(['name']);
    expect($normalized['properties']['name']['type'])->toBe('string');
    expect($normalized['properties']['platform']['enum'])->toBe(['web', 'desktop']);
});

test('json schema normalizer keeps full schema arrays and expands enum classes', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'platform' => [
                'type' => 'string',
                'enum' => Platform::class,
            ],
        ],
    ];

    $normalized = JsonSchemaNormalizer::normalize($schema);

    expect($normalized['type'])->toBe('object');
    expect($normalized['properties']['platform']['enum'])->toBe(['web', 'desktop']);
});

test('json schema normalizer encodes empty properties as a JSON object', function () {
    $normalized = JsonSchemaNormalizer::normalize([
        'type' => 'object',
        'properties' => [],
        'required' => [],
    ]);

    expect(json_encode($normalized))->toBe('{"type":"object","properties":{},"required":[]}');
});

test('json schema normalizer encodes empty nested properties as a JSON object', function () {
    $normalized = JsonSchemaNormalizer::normalize([
        'type' => 'object',
        'properties' => [
            'filters' => ['type' => 'object', 'properties' => []],
        ],
    ]);

    expect(json_encode($normalized))->toBe('{"type":"object","properties":{"filters":{"type":"object","properties":{}}}}');
});

test('json schema normalizer encodes every empty map of schemas as a JSON object', function (string $keyword) {
    $normalized = JsonSchemaNormalizer::normalize([
        'type' => 'object',
        $keyword => [],
    ]);

    expect(json_encode($normalized))->toBe('{"type":"object","'.$keyword.'":{}}');
})->with(['patternProperties', 'definitions', '$defs', 'dependentSchemas']);
