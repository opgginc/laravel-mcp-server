<?php

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;

class TestPrompt extends Prompt
{
    public string $name = 'test-prompt';

    public ?string $description = 'A test prompt';

    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'The user name',
            'required' => true,
        ],
        [
            'name' => 'role',
            'description' => 'The user role',
            'required' => false,
        ],
    ];

    public string $text = 'Hello {username}, you are a {role}!';
}

class SimplePrompt extends Prompt
{
    public string $name = 'simple';

    public string $text = 'This is a simple prompt without arguments.';
}

test('prompt renders with all arguments', function () {
    $prompt = new TestPrompt;

    $result = $prompt->render([
        'username' => 'Alice',
        'role' => 'admin',
    ]);

    expect($result)->toMatchArray([
        'description' => 'A test prompt',
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    'type' => 'text',
                    'text' => 'Hello Alice, you are a admin!',
                ],
            ],
        ],
    ]);
});

test('prompt validates required arguments', function () {
    $prompt = new TestPrompt;

    expect(fn () => $prompt->render(['role' => 'user']))
        ->toThrow(InvalidArgumentException::class, "Required argument 'username' is missing");
});

test('prompt renders with optional arguments missing', function () {
    $prompt = new TestPrompt;

    $result = $prompt->render(['username' => 'Bob']);

    expect($result['messages'][0]['content']['text'])
        ->toBe('Hello Bob, you are a {role}!');
});

test('prompt toArray includes arguments when present', function () {
    $prompt = new TestPrompt;

    $array = $prompt->toArray();

    expect($array)->toHaveKey('arguments')
        ->and($array['arguments'])->toHaveCount(2);
});

test('prompt toArray excludes arguments when empty', function () {
    $prompt = new SimplePrompt;

    $array = $prompt->toArray();

    expect($array)->not->toHaveKey('arguments')
        ->and($array)->toMatchArray([
            'name' => 'simple',
        ]);
});

test('repository can render prompt by name', function () {
    $repository = new PromptRepository;
    $repository->registerPrompt(new TestPrompt);

    $result = $repository->render('test-prompt', [
        'username' => 'Charlie',
        'role' => 'developer',
    ]);

    expect($result['messages'][0]['content']['text'])
        ->toBe('Hello Charlie, you are a developer!');
});

test('repository returns null for unknown prompt', function () {
    $repository = new PromptRepository;

    $result = $repository->render('unknown-prompt');

    expect($result)->toBeNull();
});

test('multiple prompts can be registered', function () {
    $repository = new PromptRepository;

    $repository->registerPrompts([
        new TestPrompt,
        new SimplePrompt,
    ]);

    $schemas = $repository->getPromptSchemas();
    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['name'])->toBe('test-prompt')
        ->and($schemas[1]['name'])->toBe('simple');
});

test('prompt with complex text template', function () {
    $prompt = new class extends Prompt
    {
        public string $name = 'complex-prompt';

        public array $arguments = [
            ['name' => 'task', 'required' => true],
            ['name' => 'context', 'required' => false],
        ];

        public string $text = <<<'PROMPT'
You are helping with {task}.

Context: {context}

Please proceed step by step.
PROMPT;
    };

    $result = $prompt->render([
        'task' => 'code review',
        'context' => 'Laravel application',
    ]);

    expect($result['messages'][0]['content']['text'])->toBe(<<<'EXPECTED'
You are helping with code review.

Context: Laravel application

Please proceed step by step.
EXPECTED);
});
