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
