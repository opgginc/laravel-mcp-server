<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('MCP/ResourceTemplates'));
});

test('make:mcp-resource-template generates a template class', function () {
    $path = app_path('MCP/ResourceTemplates/TestTemplate.php');

    $this->artisan('make:mcp-resource-template', ['name' => 'Test'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});
