<?php

test('tool with output schema returns structured content', function () {
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
    expect($data['result']['content'][0])->toHaveKey('structuredContent');
    
    $content = $data['result']['content'][0]['structuredContent'];
    expect($content)->toBeArray();
    expect($content)->toHaveKey('name');
    expect($content)->toHaveKey('message');
    expect($content['name'])->toBe('Tester');
    expect($content['message'])->toContain('Hello, HelloWorld `Tester` developer');
});

test('version check tool with output schema returns structured content', function () {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/call',
        'params' => [
            'name' => 'check-version',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['content'][0]['type'])->toBe('structured');
    expect($data['result']['content'][0])->toHaveKey('structuredContent');
    
    $content = $data['result']['content'][0]['structuredContent'];
    expect($content)->toBeArray();
    expect($content)->toHaveKey('version');
    expect($content)->toHaveKey('timestamp');
    expect($content['version'])->toBeString();
    expect($content['timestamp'])->toBeString();
});

test('tools list includes output schemas for compliant tools', function () {
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
    expect($data['result'])->toHaveKey('tools');
    
    $tools = $data['result']['tools'];
    expect($tools)->toBeArray();
    
    // Find the hello-world tool
    $helloTool = null;
    foreach ($tools as $tool) {
        if ($tool['name'] === 'hello-world') {
            $helloTool = $tool;
            break;
        }
    }
    
    expect($helloTool)->not->toBeNull();
    expect($helloTool)->toHaveKey('outputSchema');
    expect($helloTool['outputSchema']['type'])->toBe('object');
    expect($helloTool['outputSchema']['properties'])->toHaveKey('name');
    expect($helloTool['outputSchema']['properties'])->toHaveKey('message');
    expect($helloTool['outputSchema']['required'])->toBe(['name', 'message']);
});

test('output schema validation rejects invalid data', function () {
    // Create a mock tool that returns invalid data for its schema
    $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface {
        public function name(): string
        {
            return 'invalid-output-tool';
        }

        public function description(): string
        {
            return 'Tool that returns invalid output';
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
                    'requiredField' => [
                        'type' => 'string',
                    ],
                ],
                'required' => ['requiredField'],
            ];
        }

        public function execute(array $arguments): array
        {
            // Return invalid data - missing required field
            return ['wrongField' => 'value'];
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
    expect($data)->toHaveKey('error');
    expect($data['error'])->toContain('Tool output validation failed');
    expect($data['error'])->toContain('Missing required property: requiredField');
});

test('backward compatibility - tools without output schema return text content', function () {
    // Create a mock tool without output schema
    $mockTool = new class implements \OPGG\LaravelMcpServer\Services\ToolService\ToolInterface {
        public function name(): string
        {
            return 'legacy-tool';
        }

        public function description(): string
        {
            return 'Legacy tool without output schema';
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
            return 'Legacy tool response';
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
            'name' => 'legacy-tool',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['jsonrpc'])->toBe('2.0');
    expect($data['id'])->toBe(1);
    expect($data['result']['content'][0]['type'])->toBe('text');
    expect($data['result']['content'][0]['text'])->toBe('Legacy tool response');
});

test('legacy tools without output schema are not included in tool schemas', function () {
    $payload = [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
        'params' => [],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    $tools = $data['result']['tools'];
    
    // Find the legacy tool (should not have outputSchema)
    $legacyTool = null;
    foreach ($tools as $tool) {
        if ($tool['name'] === 'legacy-tool') {
            $legacyTool = $tool;
            break;
        }
    }
    
    expect($legacyTool)->not->toBeNull();
    expect($legacyTool)->not->toHaveKey('outputSchema');
});