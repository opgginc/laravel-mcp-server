<?php

use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\TabularTool;

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

test('tabular tool responses include CSV and Markdown helpers', function () {
    app(ToolRepository::class)->register(TabularTool::class);

    $payload = [
        'jsonrpc' => '2.0',
        'id' => 99,
        'method' => 'tools/call',
        'params' => [
            'name' => 'tabular-tool',
            'arguments' => [],
        ],
    ];

    $response = $this->postJson('/mcp', $payload);

    $response->assertStatus(200);
    $content = $response->json('result.content');

    expect($content)->toHaveCount(3);
    expect($content[0]['mimeType'])->toBe('application/json');
    expect($content[1]['mimeType'])->toBe('text/csv');
    expect($content[1]['text'])
        ->toContain("champion_id,champion_key,champion_name,release_date");
    expect($content[2]['mimeType'])->toBe('text/markdown');
    expect($content[2]['text'])
        ->toContain('| champion_id | champion_key | champion_name | release_date |');
});
