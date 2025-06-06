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
}

class UserResourceTemplate extends ResourceTemplate
{
    public string $uriTemplate = 'database://users/{userId}/profile';
    public string $name = 'User Profiles';
    public ?string $description = 'Access user profile data by user ID';
    public ?string $mimeType = 'application/json';
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
    $repository = new ResourceRepository();
    $repository->registerResourceTemplate(new TestResourceTemplate());
    
    // Register a concrete resource that matches the template
    $repository->registerResource(new DynamicLogResource('2024-01-15'));
    
    $content = $repository->read('file:///logs/2024-01-15.log');
    
    expect($content)->toMatchArray([
        'uri' => 'file:///logs/2024-01-15.log',
        'mimeType' => 'text/plain',
        'text' => "Log entries for 2024-01-15\n[2024-01-01 10:00:00] INFO: Application started",
    ]);
});

test('multiple templates can be registered', function () {
    $repository = new ResourceRepository();
    
    $repository->registerResourceTemplates([
        new TestResourceTemplate(),
        new UserResourceTemplate(),
    ]);
    
    $schemas = $repository->getTemplateSchemas();
    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['name'])->toBe('Daily Logs')
        ->and($schemas[1]['name'])->toBe('User Profiles');
});

test('template toArray filters null values', function () {
    $template = new class extends ResourceTemplate {
        public string $uriTemplate = 'file:///{id}';
        public string $name = 'Minimal Template';
        public ?string $description = null;
        public ?string $mimeType = null;
    };
    
    expect($template->toArray())->toBe([
        'uriTemplate' => 'file:///{id}',
        'name' => 'Minimal Template',
    ]);
});

