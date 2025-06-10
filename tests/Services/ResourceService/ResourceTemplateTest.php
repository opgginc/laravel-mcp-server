<?php

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceTemplate;

class TestResourceTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'file:///logs/{date}.log';

    public string $name = 'Daily Logs';

    public ?string $description = 'Access logs by date (YYYY-MM-DD)';

    public ?string $mimeType = 'text/plain';

    public function read(string $uri, array $params): array
    {
        $date = $params['date'] ?? 'unknown';

        return [
            'uri' => $uri,
            'mimeType' => $this->mimeType,
            'text' => "Log entries for {$date}\n[2024-01-01 10:00:00] INFO: Application started",
        ];
    }
}

class UserResourceTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'database://users/{userId}/profile';

    public string $name = 'User Profiles';

    public ?string $description = 'Access user profile data by user ID';

    public ?string $mimeType = 'application/json';

    public function list(): ?array
    {
        return [
            [
                'uri' => 'database://users/1/profile',
                'name' => 'User: Alice Profile',
                'description' => 'Profile data for user Alice',
                'mimeType' => $this->mimeType,
            ],
            [
                'uri' => 'database://users/2/profile',
                'name' => 'User: Bob Profile',
                'description' => 'Profile data for user Bob',
                'mimeType' => $this->mimeType,
            ],
        ];
    }

    public function read(string $uri, array $params): array
    {
        $userId = $params['userId'] ?? 'unknown';

        $userData = [
            'id' => $userId,
            'name' => "User {$userId}",
            'email' => "user{$userId}@example.com",
        ];

        return [
            'uri' => $uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode($userData),
        ];
    }
}

// Concrete resource that matches the template
class DynamicLogResource extends Resource
{
    public function __construct(private string $date)
    {
        $this->uri = "file:///logs/{$date}.log";
        $this->name = "Log for {$date}";
        $this->mimeType = 'text/plain';
    }

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => "Log entries for {$this->date}\n[2024-01-01 10:00:00] INFO: Application started",
        ];
    }
}

test('repository can match template and create resource', function () {
    $repository = new ResourceRepository;
    $repository->registerResourceTemplate(new TestResourceTemplate);

    // Register a concrete resource that matches the template
    $repository->registerResource(new DynamicLogResource('2024-01-15'));

    $content = $repository->readResource('file:///logs/2024-01-15.log');

    expect($content)->toMatchArray([
        'uri' => 'file:///logs/2024-01-15.log',
        'mimeType' => 'text/plain',
        'text' => "Log entries for 2024-01-15\n[2024-01-01 10:00:00] INFO: Application started",
    ]);
});

test('multiple templates can be registered', function () {
    $repository = new ResourceRepository;

    $repository->registerResourceTemplates([
        new TestResourceTemplate,
        new UserResourceTemplate,
    ]);

    $schemas = $repository->getTemplateSchemas();
    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['name'])->toBe('Daily Logs')
        ->and($schemas[1]['name'])->toBe('User Profiles');
});

test('template toArray filters null values', function () {
    $template = new class extends ResourceTemplate
    {
        public string $uriTemplate = 'file:///{id}';

        public string $name = 'Minimal Template';

        public ?string $description = null;

        public ?string $mimeType = null;

        public function read(string $uri, array $params): array
        {
            return ['uri' => $uri, 'text' => 'test'];
        }
    };

    expect($template->toArray())->toBe([
        'uriTemplate' => 'file:///{id}',
        'name' => 'Minimal Template',
    ]);
});

test('repository can read from template when no static resource matches', function () {
    $repository = new ResourceRepository;
    $repository->registerResourceTemplate(new TestResourceTemplate);

    $content = $repository->readResource('file:///logs/2024-01-15.log');

    expect($content)->toMatchArray([
        'uri' => 'file:///logs/2024-01-15.log',
        'mimeType' => 'text/plain',
        'text' => "Log entries for 2024-01-15\n[2024-01-01 10:00:00] INFO: Application started",
    ]);
});

test('repository prioritizes static resources over templates', function () {
    $repository = new ResourceRepository;

    // Register a template first
    $repository->registerResourceTemplate(new TestResourceTemplate);

    // Register a static resource with exact URI match
    $repository->registerResource(new DynamicLogResource('2024-01-15'));

    $content = $repository->readResource('file:///logs/2024-01-15.log');

    // Should return static resource content, not template-generated content
    expect($content['text'])->toContain('Log entries for 2024-01-15');
});

test('repository can handle multiple templates and finds correct match', function () {
    $repository = new ResourceRepository;

    $repository->registerResourceTemplates([
        new TestResourceTemplate,
        new UserResourceTemplate,
    ]);

    // Test log template
    $logContent = $repository->readResource('file:///logs/2024-12-25.log');
    expect($logContent)->toMatchArray([
        'uri' => 'file:///logs/2024-12-25.log',
        'mimeType' => 'text/plain',
    ]);

    // Test user template
    $userContent = $repository->readResource('database://users/123/profile');
    expect($userContent)->toMatchArray([
        'uri' => 'database://users/123/profile',
        'mimeType' => 'application/json',
    ]);

    $userData = json_decode($userContent['text'], true);
    expect($userData['id'])->toBe('123');
    expect($userData['name'])->toBe('User 123');
});

test('repository returns null when no template or static resource matches', function () {
    $repository = new ResourceRepository;
    $repository->registerResourceTemplate(new TestResourceTemplate);

    $content = $repository->readResource('unknown://resource/path');

    expect($content)->toBeNull();
});

test('template matchUri returns correct parameters', function () {
    $template = new UserResourceTemplate;

    $params = $template->matchUri('database://users/456/profile');

    expect($params)->toBe(['userId' => '456']);
});

test('template matchUri returns null for non-matching URI', function () {
    $template = new UserResourceTemplate;

    $params = $template->matchUri('database://posts/123');

    expect($params)->toBeNull();
});

test('template list method returns available resources', function () {
    $template = new UserResourceTemplate;

    $resources = $template->list();

    expect($resources)->toHaveCount(2)
        ->and($resources[0])->toMatchArray([
            'uri' => 'database://users/1/profile',
            'name' => 'User: Alice Profile',
            'mimeType' => 'application/json',
        ])
        ->and($resources[1])->toMatchArray([
            'uri' => 'database://users/2/profile',
            'name' => 'User: Bob Profile',
            'mimeType' => 'application/json',
        ]);
});

test('repository includes template-listed resources in getResourceSchemas', function () {
    $repository = new ResourceRepository;
    $repository->registerResourceTemplate(new UserResourceTemplate);

    $schemas = $repository->getResourceSchemas();

    expect($schemas)->toHaveCount(2)
        ->and($schemas[0])->toMatchArray([
            'uri' => 'database://users/1/profile',
            'name' => 'User: Alice Profile',
        ])
        ->and($schemas[1])->toMatchArray([
            'uri' => 'database://users/2/profile',
            'name' => 'User: Bob Profile',
        ]);
});

test('repository combines static and template resources in getResourceSchemas', function () {
    $repository = new ResourceRepository;

    // Add a static resource
    $repository->registerResource(new DynamicLogResource('2024-01-01'));

    // Add a template with list
    $repository->registerResourceTemplate(new UserResourceTemplate);

    $schemas = $repository->getResourceSchemas();

    expect($schemas)->toHaveCount(3) // 1 static + 2 template resources
        ->and($schemas[0])->toMatchArray([
            'uri' => 'file:///logs/2024-01-01.log',
            'name' => 'Log for 2024-01-01',
        ])
        ->and($schemas[1])->toMatchArray([
            'uri' => 'database://users/1/profile',
            'name' => 'User: Alice Profile',
        ])
        ->and($schemas[2])->toMatchArray([
            'uri' => 'database://users/2/profile',
            'name' => 'User: Bob Profile',
        ]);
});

test('template list method can return null for no listing support', function () {
    $template = new TestResourceTemplate;

    $resources = $template->list();

    expect($resources)->toBeNull();
});
