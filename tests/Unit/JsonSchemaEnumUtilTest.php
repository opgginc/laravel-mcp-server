<?php

use OPGG\LaravelMcpServer\Services\ToolService\Examples\Enums\Platform;
use OPGG\LaravelMcpServer\Utils\JsonSchemaEnumUtil;

test('json schema enum util expands enum class references', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'platform' => [
                'type' => 'string',
                'enum' => Platform::class,
            ],
        ],
        'required' => ['platform'],
    ];

    $normalized = JsonSchemaEnumUtil::normalizeEnumClassReferences($schema);

    expect($normalized['properties']['platform']['enum'])->toBe(['web', 'desktop']);
});

test('json schema enum util preserves non-enum-class values', function () {
    $schema = [
        'type' => 'object',
        'properties' => [
            'mode' => [
                'type' => 'string',
                'enum' => ['fast', 'safe'],
            ],
        ],
    ];

    $normalized = JsonSchemaEnumUtil::normalizeEnumClassReferences($schema);

    expect($normalized)->toBe($schema);
});
