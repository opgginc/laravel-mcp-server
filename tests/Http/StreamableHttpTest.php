<?php

use OPGG\LaravelMcpServer\Server\MCPServer;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\TabularChampionsTool;

test('streamable http GET returns method not allowed', function () {
    $response = $this->get('/mcp');

    $response->assertStatus(405)
        ->assertJson([
            'jsonrpc' => '2.0',
            'error' => 'Method Not Allowed',
        ]);
});

test('tool can be called via streamable http', function () {
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
    expect($data['result']['content'][0]['type'])->toBe('text');
    expect($data['result']['content'][0]['text'])
        ->toContain('HelloWorld `Tester` developer');

    $decoded = json_decode($data['result']['content'][0]['text'], true);
    expect($decoded['name'])->toBe('Tester');
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

test('tool can respond with csv content when using the tabular helpers', function () {
    $tools = config('mcp-server.tools');
    $tools[] = TabularChampionsTool::class;
    config()->set('mcp-server.tools', array_values(array_unique($tools)));

    app()->forgetInstance(ToolRepository::class);
    app()->forgetInstance(MCPServer::class);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 2,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tabular-champions',
            'arguments' => [
                'format' => 'csv',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['result']['content'][0]['type'])->toBe('text/csv');
    expect($data['result']['content'][0]['text'])
        ->toContain('champion_id,key,name')
        ->toContain('1,Annie,Annie');
});

test('tool can respond with markdown table content', function () {
    $tools = config('mcp-server.tools');
    $tools[] = TabularChampionsTool::class;
    config()->set('mcp-server.tools', array_values(array_unique($tools)));

    app()->forgetInstance(ToolRepository::class);
    app()->forgetInstance(MCPServer::class);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 3,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tabular-champions',
            'arguments' => [
                'format' => 'markdown',
            ],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data['result']['content'][0]['type'])->toBe('text/markdown');
    expect($data['result']['content'][0]['text'])
        ->toContain('| champion_id | key | name |')
        ->toContain('| 1 | Annie | Annie |');
});
