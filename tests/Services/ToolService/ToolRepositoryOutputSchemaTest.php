<?php

use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

test('tool repository includes output schema in schemas when present', function () {
    $toolWithSchema = new class implements ToolInterface {
        public function name(): string
        {
            return 'tool-with-schema';
        }

        public function description(): string
        {
            return 'Tool with output schema';
        }

        public function inputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];
        }

        public function annotations(): array
        {
            return [];
        }

        public function outputSchema(): ?array
        {
            return [
                'type' => 'object',
                'properties' => [
                    'result' => ['type' => 'string']
                ],
                'required' => ['result']
            ];
        }

        public function execute(array $arguments): array
        {
            return ['result' => 'success'];
        }
    };

    $repository = new ToolRepository();
    $repository->register($toolWithSchema);
    
    $schemas = $repository->getToolSchemas();
    
    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['name'])->toBe('tool-with-schema');
    expect($schemas[0])->toHaveKey('outputSchema');
    expect($schemas[0]['outputSchema']['type'])->toBe('object');
    expect($schemas[0]['outputSchema']['properties']['result']['type'])->toBe('string');
    expect($schemas[0]['outputSchema']['required'])->toBe(['result']);
});

test('tool repository excludes output schema when not present', function () {
    $toolWithoutSchema = new class implements ToolInterface {
        public function name(): string
        {
            return 'tool-without-schema';
        }

        public function description(): string
        {
            return 'Tool without output schema';
        }

        public function inputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];
        }

        public function annotations(): array
        {
            return [];
        }

        public function execute(array $arguments): string
        {
            return 'success';
        }
    };

    $repository = new ToolRepository();
    $repository->register($toolWithoutSchema);
    
    $schemas = $repository->getToolSchemas();
    
    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['name'])->toBe('tool-without-schema');
    expect($schemas[0])->not->toHaveKey('outputSchema');
});

test('tool repository excludes output schema when method returns null', function () {
    $toolWithNullSchema = new class implements ToolInterface {
        public function name(): string
        {
            return 'tool-with-null-schema';
        }

        public function description(): string
        {
            return 'Tool with null output schema';
        }

        public function inputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];
        }

        public function annotations(): array
        {
            return [];
        }

        public function outputSchema(): ?array
        {
            return null;
        }

        public function execute(array $arguments): string
        {
            return 'success';
        }
    };

    $repository = new ToolRepository();
    $repository->register($toolWithNullSchema);
    
    $schemas = $repository->getToolSchemas();
    
    expect($schemas)->toHaveCount(1);
    expect($schemas[0]['name'])->toBe('tool-with-null-schema');
    expect($schemas[0])->not->toHaveKey('outputSchema');
});

test('tool repository handles mixed tools with and without output schemas', function () {
    $toolWithSchema = new class implements ToolInterface {
        public function name(): string
        {
            return 'tool-with-schema';
        }

        public function description(): string
        {
            return 'Tool with output schema';
        }

        public function inputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];
        }

        public function annotations(): array
        {
            return [];
        }

        public function outputSchema(): ?array
        {
            return [
                'type' => 'string'
            ];
        }

        public function execute(array $arguments): string
        {
            return 'success';
        }
    };

    $toolWithoutSchema = new class implements ToolInterface {
        public function name(): string
        {
            return 'tool-without-schema';
        }

        public function description(): string
        {
            return 'Tool without output schema';
        }

        public function inputSchema(): array
        {
            return [
                'type' => 'object',
                'properties' => [],
                'required' => [],
            ];
        }

        public function annotations(): array
        {
            return [];
        }

        public function execute(array $arguments): string
        {
            return 'success';
        }
    };

    $repository = new ToolRepository();
    $repository->register($toolWithSchema);
    $repository->register($toolWithoutSchema);
    
    $schemas = $repository->getToolSchemas();
    
    expect($schemas)->toHaveCount(2);
    
    // Find tools by name
    $schemaToolIndex = null;
    $noSchemaToolIndex = null;
    
    foreach ($schemas as $index => $schema) {
        if ($schema['name'] === 'tool-with-schema') {
            $schemaToolIndex = $index;
        } elseif ($schema['name'] === 'tool-without-schema') {
            $noSchemaToolIndex = $index;
        }
    }
    
    expect($schemaToolIndex)->not->toBeNull();
    expect($noSchemaToolIndex)->not->toBeNull();
    
    expect($schemas[$schemaToolIndex])->toHaveKey('outputSchema');
    expect($schemas[$schemaToolIndex]['outputSchema']['type'])->toBe('string');
    
    expect($schemas[$noSchemaToolIndex])->not->toHaveKey('outputSchema');
});