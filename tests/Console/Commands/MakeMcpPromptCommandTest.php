<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Prompts'));
});

test('make:mcp-prompt generates a prompt class', function () {
    $path = app_path('MCP/Prompts/TestPrompt.php');

    $this->artisan('make:mcp-prompt', ['name' => 'Test'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});
