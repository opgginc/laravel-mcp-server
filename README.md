<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Build a route-first MCP server in Laravel and Lumen
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Official Website</a>
</p>

<p align="center">
  <a href="README.md">English</a> |
  <a href="README.pt-BR.md">Português do Brasil</a> |
  <a href="README.ko.md">한국어</a> |
  <a href="README.ru.md">Русский</a> |
  <a href="README.zh-CN.md">简体中文</a> |
  <a href="README.zh-TW.md">繁體中文</a> |
  <a href="README.pl.md">Polski</a> |
  <a href="README.es.md">Español</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## Breaking Changes 2.0.0

- Endpoint setup moved from config-driven registration to route-driven registration.
- Streamable HTTP is the only supported transport.
- Server metadata mutators are consolidated into `setServerInfo(...)`.
- Legacy tool transport methods were removed from runtime (`messageType()`, `ProcessMessageType::SSE`).

Full migration guide: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Overview

Laravel MCP Server provides route-based MCP endpoint registration for Laravel and Lumen.

Key points:
- Streamable HTTP transport
- Route-first configuration (`Route::mcp(...)` / `McpRoute::register(...)`)
- Tool, resource, resource template, and prompt registration per endpoint
- Route cache compatible endpoint metadata

## Requirements

- PHP >= 8.2
- Laravel (Illuminate) >= 9.x
- Lumen >= 9.x (optional)

## Quick Start

### 1) Install

```bash
composer require opgginc/laravel-mcp-server
```

### 2) Register an endpoint (Laravel)

```php
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Enums\ProtocolVersion;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->setConfig(
        compactEnumExampleCount: 3,
    )
    ->setProtocolVersion(ProtocolVersion::V2025_11_25)
    ->enabledApi()
    ->tools([
        HelloWorldTool::class,
        VersionCheckTool::class,
    ]);
```

If you need compatibility with clients that do not support `2025-11-25`, set:

```php
->setProtocolVersion(ProtocolVersion::V2025_06_18)
```

### 3) Verify

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

Quick JSON-RPC check:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Lumen Setup

```php
// bootstrap/app.php
$app->withFacades();
$app->withEloquent();
$app->register(OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider::class);
```

```php
use OPGG\LaravelMcpServer\Routing\McpRoute;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;

McpRoute::register('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
    ]);
```

## Minimal Security (Production)

Use Laravel middleware on your MCP route group.

```php
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'throttle:100,1',
])->group(function (): void {
    Route::mcp('/mcp')
        ->setServerInfo(
            name: 'Secure MCP',
            version: '2.0.0',
        )
        ->tools([
            \App\MCP\Tools\MyCustomTool::class,
        ]);
});
```

## v2.0.0 Migration Notes (from v1.0.0)

- MCP endpoint setup moved from config to route registration.
- Streamable HTTP is the only transport.
- Server metadata mutators are consolidated into `setServerInfo(...)`.
- Tool migration command is available for legacy signatures:

```bash
php artisan mcp:migrate-tools
```

Full guide: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Advanced Features (Quick Links)

- Create tools: `php artisan make:mcp-tool ToolName`
- Create resources: `php artisan make:mcp-resource ResourceName`
- Create resource templates: `php artisan make:mcp-resource-template TemplateName`
- Create prompts: `php artisan make:mcp-prompt PromptName`
- Create notifications: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- Generate from OpenAPI: `php artisan make:swagger-mcp-tool <spec-url-or-file>`
- Export tools to OpenAPI: `php artisan mcp:export-openapi --output=storage/api-docs-mcp/api-docs.json`

Code references:
- Tool examples: `src/Services/ToolService/Examples/`
- Resource examples: `src/Services/ResourceService/Examples/`
- Prompt service: `src/Services/PromptService/`
- Notification handlers: `src/Server/Notification/`
- Route builder: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> MCP Tool

Generate MCP tools from a Swagger/OpenAPI spec:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Useful options:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `path`, or `none`
- `--prefix`: class-name prefix for generated tools/resources
- `--test-api`: test endpoint connectivity before generation

Generation behavior:
- In interactive mode, you can choose Tool or Resource per endpoint.
- In non-interactive mode, `GET` endpoints are generated as Resources and other methods as Tools.

### Enhanced Interactive Preview

If you run the command without `--group-by`, the generator shows an interactive preview of folder structure and file counts before creation.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Example preview output:

```text
Choose how to organize your generated tools and resources:

Tag-based grouping (organize by OpenAPI tags)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Pet, Tools/Store, Tools/User

Path-based grouping (organize by API path)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Api, Tools/Users, Tools/Orders

No grouping (everything in root folder)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/, Resources/
```

After generation, register generated tool classes on your MCP endpoint:

```php
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Generated MCP Server',
        version: '2.0.0',
    )
    ->tools([
        \App\MCP\Tools\Billing\CreateInvoiceTool::class,
        \App\MCP\Tools\Billing\UpdateInvoiceTool::class,
    ]);
```

## MCP Tools -> OpenAPI Export

Export all registered `ToolInterface` classes (via `Route::mcp(...)->tools([...])`) to an OpenAPI JSON document using each tool's `inputSchema()`.
Only endpoints configured with `->enabledApi()` are included in this export and exposed through `POST /tools/{tool_name}`.
Operations are grouped by endpoint `name` using OpenAPI `tags`.
If multiple endpoints register the same tool name, the operation keeps first-registration behavior and merges all matching endpoint names into `tags`.
If route registration is missing, the command auto-discovers tools under default paths: `app/MCP/Tools` and `app/Tools`.

```bash
# Default output: storage/api-docs-mcp/api-docs.json
php artisan mcp:export-openapi

# Custom output + metadata
php artisan mcp:export-openapi \
  --output=storage/app/mcp.openapi.json \
  --title="MCP Tools API" \
  --api-version=2.1.0

# Limit to one endpoint (id or path)
php artisan mcp:export-openapi --endpoint=/mcp

# Discover tools from additional directory paths
php artisan mcp:export-openapi --discover-path=app/MCP/Tools

# Existing output is overwritten by default
php artisan mcp:export-openapi
```

Enable Tool API route generation:

```php
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(name: 'OP.GG MCP Server', version: '2.0.0')
    ->enabledApi()
    ->tools([
        \App\MCP\Tools\GreetingTool::class,
    ]);
```

Swagger UI testing tip:
- Exported operations use `query parameters` only (no `requestBody`) for simpler manual testing.
- Required fields from each tool `inputSchema().required` are reflected in Swagger parameter validation.
- Enum fields are exported with `schema.enum` so Swagger renders dropdown selections.
- Array fields are exported with `style=form` + `explode=true` (repeat key format, e.g. `desired_output_fields=items&desired_output_fields=runes`).
- `/tools/{tool_name}` argument parsing prefers query parameters over body/form payloads to avoid Swagger conflicts.
- Enum fields without explicit `default`/`example` are auto-filled from the first enum value (or first non-null enum value).
- String fields with descriptions like `e.g., en_US, ko_KR, ja_JP` auto-infer first sample value as `default` and `example`.

## Example Tool Class

```php
<?php

namespace App\MCP\Tools;

use App\Enums\Platform;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GreetingTool implements ToolInterface
{
    public function name(): string
    {
        return 'greeting-tool';
    }

    public function description(): string
    {
        return 'Return a greeting message.';
    }

    public function inputSchema(): array
    {
        return [
            'name' => JsonSchema::string()
                ->description('Developer Name')
                ->required(),
            'platform' => JsonSchema::string()
                ->enum(Platform::class)
                ->description('Client platform')
                ->compact(),
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
        ];
    }

    public function execute(array $arguments): mixed
    {
        return [
            'message' => 'Hello '.$arguments['name'],
        ];
    }
}
```

## JsonSchema Builder (Laravel-Style)

This package provides its own JsonSchema builder under the `OPGG\LaravelMcpServer` namespace.
You can define tool schemas in a Laravel 12-style fluent format while keeping `inputSchema(): array`.

```php
<?php

namespace App\MCP\Tools;

use App\Enums\Platform;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class WeatherTool implements ToolInterface
{
    public function name(): string
    {
        return 'weather-tool';
    }

    public function description(): string
    {
        return 'Get weather by location.';
    }

    public function inputSchema(): array
    {
        return [
            'location' => JsonSchema::string()
                ->description('Location to query')
                ->required(),
            'platform' => JsonSchema::string()
                ->enum(Platform::class)
                ->description('Client platform'),
            'days' => JsonSchema::integer()
                ->min(1)
                ->max(7)
                ->default(1),
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
```

Notes:
- Existing full JSON Schema arrays are still supported.
- `enum()` accepts either an array or a `BackedEnum::class`.
- `compact()` can be chained after `enum()` to remove `enum` from emitted schema and append a compact hint to `description` (`compact()`, `compact(null)`, `compact(3)`, or `compact('custom hint')`).
- Default compact example count is `3`, and it can be overridden per endpoint via `Route::mcp(...)->setConfig(compactEnumExampleCount: N)`.
- When exporting (`tools/list`, OpenAPI), property maps are automatically normalized to JSON Schema object format.

## Example Prompt Class

```php
<?php

namespace App\MCP\Prompts;

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';

    public ?string $description = 'Generate a welcome message.';

    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'User name',
            'required' => true,
        ],
    ];

    public string $text = 'Welcome, {username}!';
}
```

## Example Resource Class

```php
<?php

namespace App\MCP\Resources;

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

class BuildInfoResource extends Resource
{
    public string $uri = 'app://build-info';

    public string $name = 'Build Info';

    public ?string $mimeType = 'application/json';

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode([
                'version' => '2.0.0',
                'environment' => app()->environment(),
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
```

## Register Examples on a Route

```php
use App\MCP\Prompts\WelcomePrompt;
use App\MCP\Resources\BuildInfoResource;
use App\MCP\Tools\GreetingTool;
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Example MCP Server',
        version: '2.0.0',
    )
    ->tools([GreetingTool::class])
    ->resources([BuildInfoResource::class])
    ->prompts([WelcomePrompt::class]);
```

## Testing and Quality Commands

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Translation

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Translate selected languages:

```bash
python scripts/translate_readme.py es ko
```

## License

This project is distributed under the MIT license.
