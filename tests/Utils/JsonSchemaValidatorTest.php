<?php

use OPGG\LaravelMcpServer\Utils\JsonSchemaValidator;
use InvalidArgumentException;

test('validates string type successfully', function () {
    $data = 'hello world';
    $schema = ['type' => 'string'];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates integer type successfully', function () {
    $data = 42;
    $schema = ['type' => 'integer'];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates number type successfully', function () {
    $data = 3.14;
    $schema = ['type' => 'number'];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates boolean type successfully', function () {
    $data = true;
    $schema = ['type' => 'boolean'];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates null type successfully', function () {
    $data = null;
    $schema = ['type' => 'null'];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates object with required properties successfully', function () {
    $data = ['name' => 'John', 'age' => 30];
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer']
        ],
        'required' => ['name', 'age']
    ];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates array with items schema successfully', function () {
    $data = ['apple', 'banana', 'cherry'];
    $schema = [
        'type' => 'array',
        'items' => ['type' => 'string']
    ];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('fails validation for wrong string type', function () {
    $data = 123;
    $schema = ['type' => 'string'];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Expected string, got integer');
});

test('fails validation for wrong integer type', function () {
    $data = 'not a number';
    $schema = ['type' => 'integer'];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Expected number, got string');
});

test('fails validation for wrong boolean type', function () {
    $data = 'not a boolean';
    $schema = ['type' => 'boolean'];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Expected boolean, got string');
});

test('fails validation for missing required properties', function () {
    $data = ['name' => 'John'];
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer']
        ],
        'required' => ['name', 'age']
    ];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Missing required property: age');
});

test('fails validation for wrong object type', function () {
    $data = 'not an object';
    $schema = ['type' => 'object'];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Expected object, got string');
});

test('fails validation for wrong array type', function () {
    $data = 'not an array';
    $schema = ['type' => 'array'];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Expected array, got string');
});

test('fails validation for array items with wrong type', function () {
    $data = [1, 2, 'not a number'];
    $schema = [
        'type' => 'array',
        'items' => ['type' => 'integer']
    ];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Array item at index 2: Expected number, got string');
});

test('validates nested objects successfully', function () {
    $data = [
        'user' => [
            'name' => 'John',
            'contact' => [
                'email' => 'john@example.com'
            ]
        ]
    ];
    $schema = [
        'type' => 'object',
        'properties' => [
            'user' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'contact' => [
                        'type' => 'object',
                        'properties' => [
                            'email' => ['type' => 'string']
                        ],
                        'required' => ['email']
                    ]
                ],
                'required' => ['name', 'contact']
            ]
        ],
        'required' => ['user']
    ];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('validates with no type constraint', function () {
    $data = 'any value';
    $schema = []; // No type specified
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('fails validation for additional properties when not allowed', function () {
    $data = ['name' => 'John', 'extraField' => 'should not be here'];
    $schema = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string']
        ],
        'additionalProperties' => false
    ];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Additional properties not allowed: extraField');
});

test('validates array length constraints', function () {
    $data = [1, 2, 3];
    $schema = [
        'type' => 'array',
        'minItems' => 2,
        'maxItems' => 5
    ];
    
    expect(JsonSchemaValidator::validate($data, $schema))->toBeTrue();
});

test('fails validation for array too short', function () {
    $data = [1];
    $schema = [
        'type' => 'array',
        'minItems' => 2
    ];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Array must have at least 2 items, got 1');
});

test('fails validation for array too long', function () {
    $data = [1, 2, 3, 4, 5, 6];
    $schema = [
        'type' => 'array',
        'maxItems' => 5
    ];
    
    expect(fn() => JsonSchemaValidator::validate($data, $schema))
        ->toThrow(InvalidArgumentException::class, 'Array must have at most 5 items, got 6');
});