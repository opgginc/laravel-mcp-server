<?php

use OPGG\LaravelMcpServer\Services\SamplingService\Sampler;

class SimpleSampler extends Sampler
{
    public array $messages = [
        [
            'role' => 'user',
            'content' => [
                'type' => 'text',
                'text' => 'hi',
            ],
        ],
    ];
}

test('sampler toArray filters null values', function () {
    $sampler = new SimpleSampler;

    expect($sampler->toArray())->toBe([
        'messages' => $sampler->messages,
    ]);
});

test('sampler includes optional fields', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [];

        public ?array $modelPreferences = ['hints' => [['name' => 'claude']]];

        public ?string $systemPrompt = 'sys';

        public ?int $maxTokens = 5;
    };

    expect($sampler->toArray())->toBe([
        'messages' => [],
        'modelPreferences' => ['hints' => [['name' => 'claude']]],
        'systemPrompt' => 'sys',
        'maxTokens' => 5,
    ]);
});

test('sampler handles complex message content', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [
            [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Analyze this:'],
                    ['type' => 'image', 'data' => 'base64data', 'mimeType' => 'image/png'],
                ],
            ],
            [
                'role' => 'assistant',
                'content' => [
                    'type' => 'text',
                    'text' => 'I can see an image...',
                ],
            ],
        ];
    };

    $result = $sampler->toArray();

    expect($result['messages'])->toHaveCount(2);
    expect($result['messages'][0]['role'])->toBe('user');
    expect($result['messages'][0]['content'])->toHaveCount(2);
    expect($result['messages'][1]['role'])->toBe('assistant');
});

test('sampler handles model preferences with capabilities', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [];

        public ?array $modelPreferences = [
            'hints' => [
                ['name' => 'claude'],
                ['name' => 'gpt-4'],
            ],
            'capabilities' => ['intelligence', 'reasoning'],
        ];
    };

    $result = $sampler->toArray();

    expect($result['modelPreferences']['hints'])->toHaveCount(2);
    expect($result['modelPreferences']['capabilities'])->toContain('intelligence');
    expect($result['modelPreferences']['capabilities'])->toContain('reasoning');
});

test('sampler handles empty messages array', function () {
    $sampler = new class extends Sampler
    {
        public array $messages = [];
    };

    $result = $sampler->toArray();

    expect($result['messages'])->toBe([]);
    expect($result)->not->toHaveKey('modelPreferences');
    expect($result)->not->toHaveKey('systemPrompt');
    expect($result)->not->toHaveKey('maxTokens');
});

test('sampler preserves message structure exactly', function () {
    $originalMessages = [
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
                'type' => 'text',
                'text' => 'What is 2+2?',
            ],
        ],
        [
            'role' => 'assistant',
            'content' => [
                'type' => 'text',
                'text' => '2+2 equals 4.',
            ],
        ],
    ];

    $sampler = new class($originalMessages) extends Sampler
    {
        public function __construct(public array $messages) {}
    };

    $result = $sampler->toArray();

    expect($result['messages'])->toBe($originalMessages);
});
