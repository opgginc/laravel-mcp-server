<?php

use OPGG\LaravelMcpServer\Server\Request\ResourcesListHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesReadHandler;
use OPGG\LaravelMcpServer\Services\ResourceService\Resource;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;

it('lists registered resources', function () {
    $repo = new ResourceRepository;
    $repo->register(new Resource('memory://hello', 'Hello Resource', 'simple', 'text/plain', fn () => ['text' => 'hello']));

    $handler = new ResourcesListHandler($repo);
    $result = $handler->execute('resources/list');

    expect($result)->toBe([
        'resources' => [[
            'uri' => 'memory://hello',
            'name' => 'Hello Resource',
            'description' => 'simple',
            'mimeType' => 'text/plain',
        ]],
        'resourceTemplates' => [],
    ]);
});

it('reads resource content', function () {
    $repo = new ResourceRepository;
    $repo->register(new Resource('memory://hello', 'Hello Resource', null, 'text/plain', fn () => ['text' => 'hello']));

    $handler = new ResourcesReadHandler($repo);
    $result = $handler->execute('resources/read', ['uri' => 'memory://hello']);

    expect($result)->toBe([
        'contents' => [[
            'uri' => 'memory://hello',
            'mimeType' => 'text/plain',
            'text' => 'hello',
        ]],
    ]);
});
