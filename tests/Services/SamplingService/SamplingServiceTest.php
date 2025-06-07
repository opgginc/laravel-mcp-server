<?php

use OPGG\LaravelMcpServer\Data\ProcessMessageData;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Services\SamplingService\Sampler;
use OPGG\LaravelMcpServer\Services\SamplingService\SamplingServerInterface;
use OPGG\LaravelMcpServer\Services\SamplingService\SamplingService;

// Create a test double for MCPServer since it's final
class MockMCPServer implements SamplingServerInterface
{
    public array $capturedMessages = [];

    public function requestMessage(string $clientId, array $message): ProcessMessageData
    {
        $this->capturedMessages[] = ['clientId' => $clientId, 'message' => $message];

        return new ProcessMessageData(
            ProcessMessageType::HTTP,
            ['result' => 'success']
        );
    }
}

beforeEach(function () {
    $this->server = new MockMCPServer;
    $this->samplingService = new SamplingService($this->server);
});

test('sampling service creates message with sampler object', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Hello',
                ],
            ],
        ];

        public ?string $systemPrompt = 'Be helpful';

        public ?int $maxTokens = 100;
    };

    $result = $this->samplingService->createMessage('test-client', $sampler);

    expect($result)->toBeInstanceOf(ProcessMessageData::class);
    expect($this->server->capturedMessages)->toHaveCount(1);

    $captured = $this->server->capturedMessages[0];
    expect($captured['clientId'])->toBe('test-client');

    $message = $captured['message'];
    expect($message['jsonrpc'])->toBe('2.0');
    expect($message['method'])->toBe('sampling/createMessage');
    expect($message['params'])->toBe([
        'messages' => $sampler->messages,
        'systemPrompt' => 'Be helpful',
        'maxTokens' => 100,
    ]);
    expect($message['id'])->toBeString();
});

test('sampling service creates message with array params', function () {
    $params = [
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Test message',
                ],
            ],
        ],
        'maxTokens' => 50,
    ];

    $result = $this->samplingService->createMessage('test-client', $params);

    expect($result)->toBeInstanceOf(ProcessMessageData::class);
    expect($this->server->capturedMessages)->toHaveCount(1);

    $captured = $this->server->capturedMessages[0];
    $message = $captured['message'];
    expect($message['params'])->toBe($params);
});

test('sampling service generates unique message ids', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Hello',
                ],
            ],
        ];
    };

    // Create three sampling requests
    $this->samplingService->createMessage('test-client', $sampler);
    $this->samplingService->createMessage('test-client', $sampler);
    $this->samplingService->createMessage('test-client', $sampler);

    expect($this->server->capturedMessages)->toHaveCount(3);

    $ids = array_map(fn ($captured) => $captured['message']['id'], $this->server->capturedMessages);

    // Verify all IDs are unique
    expect(array_unique($ids))->toHaveCount(3);

    // Verify they look like UUIDs
    foreach ($ids as $id) {
        expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    }
});

test('sampling service handles minimal sampler', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Simple message',
                ],
            ],
        ];
    };

    $result = $this->samplingService->createMessage('test-client', $sampler);

    expect($result)->toBeInstanceOf(ProcessMessageData::class);

    $captured = $this->server->capturedMessages[0];
    $params = $captured['message']['params'];

    expect($params)->toHaveKey('messages');
    expect($params)->not->toHaveKey('systemPrompt');
    expect($params)->not->toHaveKey('maxTokens');
    expect($params)->not->toHaveKey('modelPreferences');
});

test('sampling service handles complex sampler with all options', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [
            [
                'role' => 'system',
                'content' => [
                    'type' => 'text',
                    'text' => 'You are a helpful assistant.',
                ],
            ],
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Analyze this image:'],
                    ['type' => 'image', 'data' => 'base64data', 'mimeType' => 'image/png'],
                ],
            ],
        ];

        public ?array $modelPreferences = [
            'hints' => [['name' => 'claude']],
            'capabilities' => ['intelligence', 'vision'],
        ];

        public ?string $systemPrompt = 'Focus on visual analysis';

        public ?int $maxTokens = 500;
    };

    $result = $this->samplingService->createMessage('test-client', $sampler);

    expect($result)->toBeInstanceOf(ProcessMessageData::class);

    $captured = $this->server->capturedMessages[0];
    $params = $captured['message']['params'];

    expect($params)->toHaveKey('messages');
    expect($params)->toHaveKey('systemPrompt');
    expect($params)->toHaveKey('maxTokens');
    expect($params)->toHaveKey('modelPreferences');
    expect($params['systemPrompt'])->toBe('Focus on visual analysis');
    expect($params['maxTokens'])->toBe(500);
    expect($params['modelPreferences']['hints'][0]['name'])->toBe('claude');
});
