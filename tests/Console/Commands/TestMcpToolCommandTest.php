<?php

use Illuminate\Support\Facades\Route;
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
        ->expectsOutputToContain('No MCP tools are registered. Use Route::mcp(...)->tools([...]) to register endpoint tools.')
        ->assertExitCode(0);
});
