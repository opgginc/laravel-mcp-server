<?php

use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Tests\Fixtures\Resolvers\PhaseToolResolver;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\AutoStructuredArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;

test('mcp:test-tool can filter by endpoint id', function () {
    $builder = Route::mcp('/primary-mcp')
        ->tools([LegacyArrayTool::class]);

    $this->artisan('mcp:test-tool', [
        '--list' => true,
        '--endpoint' => $builder->endpointId(),
    ])
        ->expectsOutputToContain('legacy-array-tool')
        ->assertExitCode(0);
});

test('mcp:test-tool merges tools from all endpoints that share the same path filter', function () {
    Route::domain('alpha.example.com')->group(function () {
        Route::mcp('/mcp')->tools([LegacyArrayTool::class]);
    });

    Route::domain('beta.example.com')->group(function () {
        Route::mcp('/mcp')->tools([AutoStructuredArrayTool::class]);
    });

    $this->artisan('mcp:test-tool', [
        '--list' => true,
        '--endpoint' => '/mcp',
    ])
        ->expectsOutputToContain('legacy-array-tool')
        ->expectsOutputToContain('auto-structured-array-tool')
        ->assertExitCode(0);
});

test('mcp:test-tool warns when endpoint filter is not registered', function () {
    Route::mcp('/mcp')->tools([LegacyArrayTool::class]);

    $this->artisan('mcp:test-tool', [
        '--list' => true,
        '--endpoint' => '/unknown',
    ])
        ->expectsOutputToContain("Endpoint '/unknown' is not registered via Route::mcp().")
        ->expectsOutputToContain('No MCP tools are registered. Use Route::mcp(...)->tools([...]) or ->dynamicTools(...) to register endpoint tools.')
        ->assertExitCode(0);
});

test('mcp:test-tool lists declared tools from dynamic tool resolvers', function () {
    Route::mcp('/dynamic-mcp')
        ->dynamicTools(PhaseToolResolver::class);

    $this->artisan('mcp:test-tool', [
        '--list' => true,
        '--endpoint' => '/dynamic-mcp',
    ])
        ->expectsOutputToContain('legacy-array-tool')
        ->expectsOutputToContain('structured-only-tool')
        ->assertExitCode(0);
});
