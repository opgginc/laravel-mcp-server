<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(app_path('MCP/Samplers'));
});

test('make:mcp-sampler generates a sampler class', function () {
    $path = app_path('MCP/Samplers/TestSampler.php');

    $this->artisan('make:mcp-sampler', ['name' => 'Test'])
        ->expectsOutputToContain('Created')
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();
});
