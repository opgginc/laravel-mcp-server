<?php

test('streamable http GET returns method not allowed', function () {
    $response = $this->get('/mcp');

    $response->assertStatus(405)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => 'Method Not Allowed',
        ]);
});

test('tool can be called via streamable http with structured content', function () {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'hello-world',
            'arguments' => [
                'name' => 'Tester',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['content'][0]['type'])->toBe('structured');
    expect($data['result']['content'][0]['structuredContent'])->toEqual([
        'name' => 'Tester',
        'message' => 'Hello, HelloWorld `Tester` developer.',
    ]);
});

test('tool with output schema validation failure returns error', function () {
    // Create a mock tool that returns invalid output
    $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface
    {
        public function name(): string
        {
            return 'invalid-output-tool';
        }

        public function description(): string
        {
            return 'A tool that returns invalid output';
        }

        public function inputSchema(): array
        {
            return ['type' => 'object', 'properties' => []];
        }

        public function annotations(): array
        {
            return [];
        }

        public function isStreaming(): bool
        {
            return false;
        }

        public function outputSchema(): ?array
        {
            return [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'age' => ['type' => 'integer'],
                ],
                'required' => ['name', 'age'],
            ];
        }

        public function execute(array $arguments): array
        {
            // Return invalid output (missing required field)
            return ['name' => 'John'];
        }
    };

    // Register the mock tool
    $toolRepository = app(\OPGG\LaravelMcpServer\Services\ToolService\ToolRepository::class);
    $toolRepository->register($mockTool);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'invalid-output-tool',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['error']['message'])->toContain('Tool output validation failed');
    expect($data['error']['message'])->toContain('Missing required property: age');
});

test('tools/list includes output schemas for compliant tools', function () {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['tools'])->toBeArray();

    // Find the hello-world tool
    $helloWorldTool = collect($data['result']['tools'])
        ->firstWhere('name', 'hello-world');

    expect($helloWorldTool)->not->toBeNull();
    expect($helloWorldTool)->toHaveKey('outputSchema');
    expect($helloWorldTool['outputSchema']['type'])->toBe('object');
    expect($helloWorldTool['outputSchema']['properties'])->toHaveKeys(['name', 'message']);
});

test('tool without output schema still works with text content', function () {
    // Create a mock tool without outputSchema method
    $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface
    {
        public function name(): string
        {
            return 'text-only-tool';
        }

        public function description(): string
        {
            return 'A tool without output schema';
        }

        public function inputSchema(): array
        {
            return ['type' => 'object', 'properties' => []];
        }

        public function annotations(): array
        {
            return [];
        }

        public function isStreaming(): bool
        {
            return false;
        }

        public function execute(array $arguments): string
        {
            return 'Simple text result';
        }
    };

    // Register the mock tool
    $toolRepository = app(\OPGG\LaravelMcpServer\Services\ToolService\ToolRepository::class);
    $toolRepository->register($mockTool);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'text-only-tool',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['content'][0]['type'])->toBe('text');
    expect($data['result']['content'][0]['text'])->toBe('Simple text result');
});

test('notification returns HTTP 202 with no body', function () {
    $payload = [
        'jsonrpc' => '2.0',
        'method' => 'notifications/initialized',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(202);
    expect($response->getContent())->toBe('');
});
