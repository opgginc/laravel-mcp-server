<?php

use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

describe('Output Schema Tool Support', function () {
    it('includes output schema in tool schemas when implemented', function () {
        $repository = new ToolRepository();
        $repository->register(HelloWorldTool::class);
        
        $schemas = $repository->getToolSchemas();
        
        expect($schemas)->toHaveCount(1);
        expect($schemas[0])->toHaveKey('outputSchema');
        expect($schemas[0]['outputSchema'])->toEqual([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The developer name that was greeted',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'The hello world greeting message',
                ],
            ],
            'required' => ['name', 'message'],
        ]);
    });

    it('hello world tool returns structured data matching output schema', function () {
        $tool = new HelloWorldTool();
        
        $result = $tool->execute(['name' => 'TestUser']);
        
        expect($result)->toEqual([
            'name' => 'TestUser',
            'message' => 'Hello, HelloWorld `TestUser` developer.',
        ]);
        
        // Verify it matches the output schema
        $schema = $tool->outputSchema();
        expect($schema)->not->toBeNull();
        
        // This should not throw an exception
        \OPGG\LaravelMcpServer\Utils\JsonSchemaValidator::validate($result, $schema);
    });

    it('version check tool returns structured data matching output schema', function () {
        $tool = new VersionCheckTool();
        
        $result = $tool->execute([]);
        
        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['version', 'timestamp', 'message']);
        expect($result['version'])->toBeString();
        expect($result['timestamp'])->toBeString();
        expect($result['message'])->toBeString();
        
        // Verify it matches the output schema
        $schema = $tool->outputSchema();
        expect($schema)->not->toBeNull();
        
        // This should not throw an exception
        \OPGG\LaravelMcpServer\Utils\JsonSchemaValidator::validate($result, $schema);
    });

    it('tool repository handles tools without output schema', function () {
        // Create a mock tool without outputSchema method
        $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface {
            public function name(): string { return 'mock-tool'; }
            public function description(): string { return 'A mock tool'; }
            public function inputSchema(): array { return ['type' => 'object', 'properties' => []]; }
            public function annotations(): array { return []; }
            public function execute(array $arguments): mixed { return 'mock result'; }
        };
        
        $repository = new ToolRepository();
        $repository->register($mockTool);
        
        $schemas = $repository->getToolSchemas();
        
        expect($schemas)->toHaveCount(1);
        expect($schemas[0])->not->toHaveKey('outputSchema');
    });

    it('tool repository handles tools with null output schema', function () {
        // Create a mock tool that returns null outputSchema
        $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface {
            public function name(): string { return 'mock-tool'; }
            public function description(): string { return 'A mock tool'; }
            public function inputSchema(): array { return ['type' => 'object', 'properties' => []]; }
            public function annotations(): array { return []; }
            public function execute(array $arguments): mixed { return 'mock result'; }
            public function outputSchema(): ?array { return null; }
        };
        
        $repository = new ToolRepository();
        $repository->register($mockTool);
        
        $schemas = $repository->getToolSchemas();
        
        expect($schemas)->toHaveCount(1);
        expect($schemas[0])->not->toHaveKey('outputSchema');
    });
});