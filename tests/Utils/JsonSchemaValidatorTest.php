<?php

use OPGG\LaravelMcpServer\Utils\JsonSchemaValidator;
use InvalidArgumentException;

describe('JsonSchemaValidator', function () {
    it('validates string type correctly', function () {
        $schema = ['type' => 'string'];
        
        expect(JsonSchemaValidator::validate('hello', $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate(123, ['type' => 'string']);
        })->toThrow(InvalidArgumentException::class, 'Expected string, got integer');
    });

    it('validates integer type correctly', function () {
        $schema = ['type' => 'integer'];
        
        expect(JsonSchemaValidator::validate(42, $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate('42', ['type' => 'integer']);
        })->toThrow(InvalidArgumentException::class, 'Expected integer, got string');
    });

    it('validates number type correctly', function () {
        $schema = ['type' => 'number'];
        
        expect(JsonSchemaValidator::validate(42, $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate(42.5, $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate('42', $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate('not-a-number', ['type' => 'number']);
        })->toThrow(InvalidArgumentException::class, 'Expected number, got string');
    });

    it('validates boolean type correctly', function () {
        $schema = ['type' => 'boolean'];
        
        expect(JsonSchemaValidator::validate(true, $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate(false, $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate(1, ['type' => 'boolean']);
        })->toThrow(InvalidArgumentException::class, 'Expected boolean, got integer');
    });

    it('validates null type correctly', function () {
        $schema = ['type' => 'null'];
        
        expect(JsonSchemaValidator::validate(null, $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate('', ['type' => 'null']);
        })->toThrow(InvalidArgumentException::class, 'Expected null, got string');
    });

    it('validates array type correctly', function () {
        $schema = ['type' => 'array'];
        
        expect(JsonSchemaValidator::validate([], $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate([1, 2, 3], $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate('not-array', ['type' => 'array']);
        })->toThrow(InvalidArgumentException::class, 'Expected array, got string');
    });

    it('validates array with items schema', function () {
        $schema = [
            'type' => 'array',
            'items' => ['type' => 'string']
        ];
        
        expect(JsonSchemaValidator::validate(['a', 'b', 'c'], $schema))->toBeTrue();
        
        expect(function () use ($schema) {
            JsonSchemaValidator::validate(['a', 123, 'c'], $schema);
        })->toThrow(InvalidArgumentException::class, 'Array item at index 1: Expected string, got integer');
    });

    it('validates object type correctly', function () {
        $schema = ['type' => 'object'];
        
        expect(JsonSchemaValidator::validate([], $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate(['key' => 'value'], $schema))->toBeTrue();
        
        expect(function () {
            JsonSchemaValidator::validate('not-object', ['type' => 'object']);
        })->toThrow(InvalidArgumentException::class, 'Expected object/array, got string');
    });

    it('validates object properties', function () {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
            ],
        ];
        
        $validData = ['name' => 'John', 'age' => 30];
        expect(JsonSchemaValidator::validate($validData, $schema))->toBeTrue();
        
        expect(function () use ($schema) {
            JsonSchemaValidator::validate(['name' => 'John', 'age' => '30'], $schema);
        })->toThrow(InvalidArgumentException::class, "Property 'age': Expected integer, got string");
    });

    it('validates required properties', function () {
        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
            ],
            'required' => ['name', 'email'],
        ];
        
        expect(JsonSchemaValidator::validate(['name' => 'John', 'email' => 'john@example.com'], $schema))->toBeTrue();
        
        expect(function () use ($schema) {
            JsonSchemaValidator::validate(['name' => 'John'], $schema);
        })->toThrow(InvalidArgumentException::class, 'Missing required property: email');
    });

    it('validates nested objects', function () {
        $schema = [
            'type' => 'object',
            'properties' => [
                'user' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string'],
                        'id' => ['type' => 'integer'],
                    ],
                    'required' => ['name'],
                ],
            ],
        ];
        
        $validData = [
            'user' => [
                'name' => 'John',
                'id' => 123,
            ],
        ];
        expect(JsonSchemaValidator::validate($validData, $schema))->toBeTrue();
        
        expect(function () use ($schema) {
            JsonSchemaValidator::validate(['user' => ['id' => 123]], $schema);
        })->toThrow(InvalidArgumentException::class, "Property 'user': Missing required property: name");
    });

    it('handles validation with result method', function () {
        $schema = ['type' => 'string'];
        
        $result = JsonSchemaValidator::validateWithResult('hello', $schema);
        expect($result)->toBe(['valid' => true]);
        
        $result = JsonSchemaValidator::validateWithResult(123, $schema);
        expect($result)->toEqual([
            'valid' => false,
            'error' => 'Expected string, got integer',
        ]);
    });

    it('allows no type constraint', function () {
        $schema = []; // No type specified
        
        expect(JsonSchemaValidator::validate('anything', $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate(123, $schema))->toBeTrue();
        expect(JsonSchemaValidator::validate([], $schema))->toBeTrue();
    });

    it('throws for unsupported schema type', function () {
        $schema = ['type' => 'unsupported-type'];
        
        expect(function () use ($schema) {
            JsonSchemaValidator::validate('test', $schema);
        })->toThrow(InvalidArgumentException::class, 'Unsupported schema type: unsupported-type');
    });
});