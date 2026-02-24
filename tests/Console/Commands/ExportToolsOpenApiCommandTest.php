<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\CompactEnumTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\DesiredOutputFieldsTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\EnumOnlyTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\JsonSchemaBuilderTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LegacyArrayTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\LocaleDescriptionTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\NullableEnumTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SharedNamePrimaryTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\SharedNameSecondaryTool;
use OPGG\LaravelMcpServer\Tests\Fixtures\Tools\TabularChampionsTool;

function exportCommandOutputPath(): string
{
    return storage_path('mcp-tools-openapi-test.json');
}

function exportCommandDefaultOutputPath(): string
{
    return storage_path('api-docs-mcp/api-docs.json');
}

beforeEach(function () {
    File::delete(exportCommandOutputPath());
    File::delete(exportCommandDefaultOutputPath());
});

afterEach(function () {
    File::delete(exportCommandOutputPath());
    File::delete(exportCommandDefaultOutputPath());
    File::deleteDirectory(dirname(exportCommandDefaultOutputPath()));
});

test('mcp:export-openapi generates openapi json from registered tools', function () {
    Route::mcp('/mcp')->enabledApi()->tools([
        TabularChampionsTool::class,
        LegacyArrayTool::class,
    ]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
        '--title' => 'MCP Tools API',
        '--api-version' => '2.1.0',
    ])
        ->expectsOutputToContain('Generated OpenAPI spec for 2 tool(s)')
        ->assertExitCode(0);

    expect(File::exists(exportCommandOutputPath()))->toBeTrue();

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/tabular-champions']['post'];

    expect($json['openapi'])->toBe('3.1.0')
        ->and($json['info']['title'])->toBe('MCP Tools API')
        ->and($json['info']['version'])->toBe('2.1.0')
        ->and($json['paths'])->toHaveKey('/tools/tabular-champions')
        ->and($json['paths'])->toHaveKey('/tools/legacy-array-tool')
        ->and($operation)->not->toHaveKey('requestBody')
        ->and($operation['parameters'][0]['name'])
        ->toBe('format')
        ->and($operation['parameters'][0]['in'])
        ->toBe('query')
        ->and($operation['parameters'][0]['schema']['enum'])
        ->toBe(['csv', 'markdown'])
        ->and($json['components']['schemas']['TabularChampionsInput']['properties']['format']['enum'])
        ->toBe(['csv', 'markdown']);
});

test('mcp:export-openapi can filter tools by endpoint id', function () {
    $primary = Route::mcp('/primary')->enabledApi()->tools([LegacyArrayTool::class]);
    Route::mcp('/secondary')->enabledApi()->tools([TabularChampionsTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
        '--endpoint' => $primary->endpointId(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);

    expect($json['paths'])->toHaveKey('/tools/legacy-array-tool')
        ->and($json['paths'])->not->toHaveKey('/tools/tabular-champions');
});

test('mcp:export-openapi discovers tool classes from filesystem path', function () {
    $fixturePath = realpath(__DIR__.'/../../fixtures/Tools');
    expect($fixturePath)->not->toBeFalse();

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
        '--discover-path' => [$fixturePath],
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);

    expect($json['paths'])->toHaveKey('/tools/legacy-array-tool')
        ->and($json['paths'])->toHaveKey('/tools/tabular-champions');
});

test('mcp:export-openapi discovers tools from default app/Tools path', function () {
    $toolDirectory = app_path('Tools');
    $toolFile = $toolDirectory.'/AutoDiscoveredDefaultPathTool.php';

    if (! File::exists($toolDirectory)) {
        File::makeDirectory($toolDirectory, 0755, true);
    }

    File::put($toolFile, <<<'PHP'
<?php

namespace App\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class AutoDiscoveredDefaultPathTool implements ToolInterface
{
    public function name(): string
    {
        return 'auto-discovered-default-path-tool';
    }

    public function description(): string
    {
        return 'Auto discovered from app/Tools path.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): mixed
    {
        return ['ok' => true];
    }
}
PHP
    );

    if (! class_exists('App\\Tools\\AutoDiscoveredDefaultPathTool')) {
        require_once $toolFile;
    }

    try {
        $this->artisan('mcp:export-openapi', [
            '--output' => exportCommandOutputPath(),
        ])->assertExitCode(0);

        $json = json_decode(File::get(exportCommandOutputPath()), true);
        expect($json['paths'])->toHaveKey('/tools/auto-discovered-default-path-tool');
    } finally {
        File::delete($toolFile);
    }
});

test('mcp:export-openapi overwrites an existing file by default', function () {
    Route::mcp('/mcp')->enabledApi()->tools([LegacyArrayTool::class]);
    File::put(exportCommandOutputPath(), '{"already":"exists"}');

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    expect($json['openapi'])->toBe('3.1.0');
});

test('mcp:export-openapi writes to api-docs-mcp path by default', function () {
    Route::mcp('/mcp')->enabledApi()->tools([LegacyArrayTool::class]);

    $this->artisan('mcp:export-openapi')->assertExitCode(0);

    expect(File::exists(exportCommandDefaultOutputPath()))->toBeTrue();

    $json = json_decode(File::get(exportCommandDefaultOutputPath()), true);
    expect($json['openapi'])->toBe('3.1.0');
});

test('mcp:export-openapi groups tool operations by endpoint name tags', function () {
    Route::mcp('/mcp')
        ->setServerInfo(name: 'OP.GG MCP Server', version: '2.0.0')
        ->enabledApi()
        ->tools([LegacyArrayTool::class]);

    Route::mcp('/mcp-voice-lol')
        ->setServerInfo(name: 'OP.GG MCP Server - Voice lol', version: '2.0.0')
        ->enabledApi()
        ->tools([TabularChampionsTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $tagNames = array_column($json['tags'], 'name');

    expect($json['paths']['/tools/legacy-array-tool']['post']['tags'])->toBe(['OP.GG MCP Server'])
        ->and($json['paths']['/tools/tabular-champions']['post']['tags'])->toBe(['OP.GG MCP Server - Voice lol'])
        ->and($tagNames)->toContain('OP.GG MCP Server')
        ->and($tagNames)->toContain('OP.GG MCP Server - Voice lol');
});

test('mcp:export-openapi exports only enabledApi endpoint tools from route registry', function () {
    Route::mcp('/enabled')->enabledApi()->tools([LegacyArrayTool::class]);
    Route::mcp('/disabled')->tools([TabularChampionsTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);

    expect($json['paths'])->toHaveKey('/tools/legacy-array-tool')
        ->and($json['paths'])->not->toHaveKey('/tools/tabular-champions');
});

test('mcp:export-openapi uses first enabled endpoint for duplicate tool names', function () {
    Route::mcp('/first')
        ->setServerInfo(name: 'First Group', version: '1.0.0')
        ->enabledApi()
        ->tools([SharedNamePrimaryTool::class]);

    Route::mcp('/second')
        ->setServerInfo(name: 'Second Group', version: '1.0.0')
        ->enabledApi()
        ->tools([SharedNameSecondaryTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);

    expect($json['paths'])->toHaveKey('/tools/shared-name-tool')
        ->and($json['paths']['/tools/shared-name-tool']['post']['tags'])->toBe(['First Group', 'Second Group']);
});

test('mcp:export-openapi allows required fields to be tested via query parameters', function () {
    Route::mcp('/mcp')->enabledApi()->tools([HelloWorldTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/hello-world']['post'];
    $platform = collect($operation['parameters'])->firstWhere('name', 'platform');

    expect($operation['parameters'])->toHaveCount(2)
        ->and($operation['parameters'][0]['name'])->toBe('name')
        ->and($operation['parameters'][0]['in'])->toBe('query')
        ->and($operation['parameters'][0]['required'])->toBeTrue()
        ->and($platform)->not->toBeNull()
        ->and($platform['in'])->toBe('query')
        ->and($platform['required'])->toBeFalse()
        ->and($platform['schema']['enum'])->toBe(['web', 'desktop'])
        ->and($operation)->not->toHaveKey('requestBody');
});

test('mcp:export-openapi supports json schema builder type map inputs', function () {
    Route::mcp('/mcp')->enabledApi()->tools([JsonSchemaBuilderTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/json-schema-builder-tool']['post'];
    $location = collect($operation['parameters'])->firstWhere('name', 'location');
    $units = collect($operation['parameters'])->firstWhere('name', 'units');
    $inputSchema = $json['components']['schemas']['JsonSchemaBuilderToolInput'];
    $outputSchema = $json['components']['schemas']['JsonSchemaBuilderToolOutput'];

    expect($location)->not->toBeNull()
        ->and($location['required'])->toBeTrue()
        ->and($location['schema']['type'])->toBe('string')
        ->and($units)->not->toBeNull()
        ->and($units['required'])->toBeFalse()
        ->and($units['schema']['enum'])->toBe(['celsius', 'fahrenheit'])
        ->and($inputSchema['required'])->toBe(['location'])
        ->and($outputSchema['required'])->toBe(['forecast', 'temperature']);
});

test('mcp:export-openapi applies endpoint compact enum config', function () {
    Route::mcp('/mcp')
        ->setConfig(compactEnumExampleCount: 1)
        ->enabledApi()
        ->tools([CompactEnumTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $property = $json['components']['schemas']['CompactEnumToolInput']['properties']['mode'];

    expect($property)->not->toHaveKey('enum')
        ->and($property['description'])->toBe('Mode Examples: alpha')
        ->and($property['default'])->toBe('alpha')
        ->and($property['example'])->toBe('alpha');
});

test('mcp:export-openapi exports required array fields as query parameters', function () {
    Route::mcp('/mcp')->enabledApi()->tools([DesiredOutputFieldsTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/desired-output-fields-tool']['post'];
    $parameter = collect($operation['parameters'])->firstWhere('name', 'desired_output_fields');
    $schema = $json['components']['schemas']['DesiredOutputFieldsToolInput']['properties']['desired_output_fields'];

    expect($parameter)->not->toBeNull()
        ->and($parameter['in'])->toBe('query')
        ->and($parameter['required'])->toBeTrue()
        ->and($parameter['style'])->toBe('form')
        ->and($parameter['explode'])->toBeTrue()
        ->and($parameter['schema']['type'])->toBe('array')
        ->and($parameter['schema']['items']['type'])->toBe('string')
        ->and($parameter['schema']['items']['enum'])->toBe(['runes', 'items', 'counters'])
        ->and($schema['type'])->toBe('array')
        ->and($schema['items']['enum'])->toBe(['runes', 'items', 'counters'])
        ->and($operation)->not->toHaveKey('requestBody');
});

test('mcp:export-openapi adds enum dropdown defaults and examples for query parameters', function () {
    Route::mcp('/mcp')->enabledApi()->tools([EnumOnlyTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/enum-only-tool']['post'];
    $parameter = $operation['parameters'][0];
    $propertySchema = $json['components']['schemas']['EnumOnlyToolInput']['properties']['mode'];

    expect($parameter['name'])->toBe('mode')
        ->and($parameter['in'])->toBe('query')
        ->and($parameter['required'])->toBeTrue()
        ->and($parameter['schema']['type'])->toBe('string')
        ->and($parameter['schema']['enum'])->toBe(['fast', 'safe'])
        ->and($parameter['schema']['default'])->toBe('fast')
        ->and($parameter['schema']['example'])->toBe('fast')
        ->and($parameter['example'])->toBe('fast')
        ->and($propertySchema['type'])->toBe('string')
        ->and($propertySchema['default'])->toBe('fast')
        ->and($propertySchema['example'])->toBe('fast');
});

test('mcp:export-openapi prefers non-null enum values for default and example', function () {
    Route::mcp('/mcp')->enabledApi()->tools([NullableEnumTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/nullable-enum-tool']['post'];
    $parameter = $operation['parameters'][0];
    $propertySchema = $json['components']['schemas']['NullableEnumToolInput']['properties']['region'];

    expect($parameter['schema']['enum'])->toBe([null, 'kr', 'na'])
        ->and($parameter['schema']['type'])->toBe('string')
        ->and($parameter['schema']['nullable'])->toBeTrue()
        ->and($parameter['schema']['default'])->toBe('kr')
        ->and($parameter['schema']['example'])->toBe('kr')
        ->and($parameter['example'])->toBe('kr')
        ->and($propertySchema['default'])->toBe('kr')
        ->and($propertySchema['example'])->toBe('kr');
});

test('mcp:export-openapi infers default and example from e.g. description text', function () {
    Route::mcp('/mcp')->enabledApi()->tools([LocaleDescriptionTool::class]);

    $this->artisan('mcp:export-openapi', [
        '--output' => exportCommandOutputPath(),
    ])->assertExitCode(0);

    $json = json_decode(File::get(exportCommandOutputPath()), true);
    $operation = $json['paths']['/tools/locale-description-tool']['post'];
    $parameter = $operation['parameters'][0];
    $propertySchema = $json['components']['schemas']['LocaleDescriptionToolInput']['properties']['lang'];

    expect($parameter['name'])->toBe('lang')
        ->and($parameter['in'])->toBe('query')
        ->and($parameter['required'])->toBeTrue()
        ->and($parameter['schema']['default'])->toBe('en_US')
        ->and($parameter['schema']['example'])->toBe('en_US')
        ->and($parameter['example'])->toBe('en_US')
        ->and($propertySchema['default'])->toBe('en_US')
        ->and($propertySchema['example'])->toBe('en_US');
});
