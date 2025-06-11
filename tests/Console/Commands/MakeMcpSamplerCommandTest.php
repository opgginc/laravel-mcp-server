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

    $content = File::get($path);
    expect($content)->toContain('class TestSampler extends Sampler');
    expect($content)->toContain('namespace App\MCP\Samplers');
    expect($content)->toContain('use OPGG\LaravelMcpServer\Services\SamplingService\Sampler');
});

test('make:mcp-sampler automatically adds sampler suffix', function () {
    $path = app_path('MCP/Samplers/QuestionSampler.php');

    $this->artisan('make:mcp-sampler', ['name' => 'Question'])
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('class QuestionSampler extends Sampler');
});

test('make:mcp-sampler handles existing sampler suffix', function () {
    $path = app_path('MCP/Samplers/AnalyzeSampler.php');

    $this->artisan('make:mcp-sampler', ['name' => 'AnalyzeSampler'])
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('class AnalyzeSampler extends Sampler');
});

test('make:mcp-sampler converts kebab case to study case', function () {
    $path = app_path('MCP/Samplers/CodeAnalysisSampler.php');

    $this->artisan('make:mcp-sampler', ['name' => 'code-analysis'])
        ->assertExitCode(0);

    expect(File::exists($path))->toBeTrue();

    $content = File::get($path);
    expect($content)->toContain('class CodeAnalysisSampler extends Sampler');
});

test('make:mcp-sampler fails when class already exists', function () {
    $path = app_path('MCP/Samplers/ExistingSampler.php');

    // Create the directory and file first
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<?php class ExistingSampler {}');

    $this->artisan('make:mcp-sampler', ['name' => 'Existing'])
        ->expectsOutputToContain('already exists')
        ->assertExitCode(1);
});

test('make:mcp-sampler creates directory if it does not exist', function () {
    $dirPath = app_path('MCP/Samplers');
    expect(File::exists($dirPath))->toBeFalse();

    $this->artisan('make:mcp-sampler', ['name' => 'New'])
        ->assertExitCode(0);

    expect(File::exists($dirPath))->toBeTrue();
    expect(File::exists($dirPath.'/NewSampler.php'))->toBeTrue();
});
