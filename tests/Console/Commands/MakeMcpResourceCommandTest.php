<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Resources'));
});

test('make:mcp-resource generates a resource class', function () {
    $path = app_path('MCP/Resources/TestResource.php');

    $this->artisan('make:mcp-resource', ['name' => 'Test'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});
