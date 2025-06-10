<?php

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

class TestResource extends Resource
{
    public string $uri = 'file:///test.txt';

    public string $name = 'Test Resource';

    public ?string $description = 'A test resource';

    public ?string $mimeType = 'text/plain';

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => 'Test content',
        ];
    }
}

class BinaryResource extends Resource
{
    public string $uri = 'file:///image.png';

    public string $name = 'Binary Resource';

    public ?string $mimeType = 'image/png';

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'blob' => base64_encode('fake binary data'),
        ];
    }
}

test('resource can be registered in repository', function () {
    $repository = new ResourceRepository;
    $resource = new TestResource;

    $repository->registerResource($resource);

    $schemas = $repository->getResourceSchemas();
    expect($schemas)->toHaveCount(1)
        ->and($schemas[0])->toMatchArray([
            'uri' => 'file:///test.txt',
            'name' => 'Test Resource',
            'description' => 'A test resource',
            'mimeType' => 'text/plain',
        ]);
});

test('resource read returns binary content as blob', function () {
    $resource = new BinaryResource;

    $content = $resource->read();

    expect($content)->toHaveKey('blob')
        ->and($content['blob'])->toBe(base64_encode('fake binary data'))
        ->and($content)->not->toHaveKey('text');
});

test('repository can read resource by uri', function () {
    $repository = new ResourceRepository;
    $repository->registerResource(new TestResource);

    $content = $repository->readResource('file:///test.txt');

    expect($content)->toMatchArray([
        'uri' => 'file:///test.txt',
        'mimeType' => 'text/plain',
        'text' => 'Test content',
    ]);
});

test('repository returns null for unknown resource uri', function () {
    $repository = new ResourceRepository;

    $content = $repository->readResource('file:///unknown.txt');

    expect($content)->toBeNull();
});

test('resource toArray filters null values', function () {
    $resource = new class extends Resource
    {
        public string $uri = 'file:///minimal.txt';

        public string $name = 'Minimal Resource';

        public ?string $description = null;

        public ?string $mimeType = null;

        public function read(): array
        {
            return ['uri' => $this->uri, 'text' => 'content'];
        }
    };

    expect($resource->toArray())->toBe([
        'uri' => 'file:///minimal.txt',
        'name' => 'Minimal Resource',
    ]);
});

test('multiple resources can be registered', function () {
    $repository = new ResourceRepository;

    $repository->registerResources([
        new TestResource,
        new BinaryResource,
    ]);

    $schemas = $repository->getResourceSchemas();
    expect($schemas)->toHaveCount(2)
        ->and($schemas[0]['uri'])->toBe('file:///test.txt')
        ->and($schemas[1]['uri'])->toBe('file:///image.png');
});
