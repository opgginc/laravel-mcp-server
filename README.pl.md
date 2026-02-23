<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
Zbuduj pierwszy serwer MCP w Laravel i Lumen
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

## Przełomowe zmiany 2.0.0

- Konfiguracja punktu końcowego została przeniesiona z rejestracji opartej na konfiguracji do rejestracji opartej na trasach.
- Jedynym obsługiwanym transportem jest przesyłany strumieniowo protokół HTTP.
- Mutatory metadanych serwera są konsolidowane w `setServerInfo(...)`.
- Starsze metody transportu narzędzi zostały usunięte ze środowiska wykonawczego (`messageType()`, `ProcessMessageType::SSE`).

Pełny przewodnik po migracji: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Przegląd

Laravel MCP Server zapewnia rejestrację punktów końcowych MCP opartą na trasach dla Laravel i Lumen.

Kluczowe punkty:
- Strumieniowy transport HTTP
- Konfiguracja pierwszej trasy (`Route::mcp(...)` / `McpRoute::register(...)`)
- Narzędzie, zasób, szablon zasobu i rejestracja monitu dla każdego punktu końcowego
- Metadane punktu końcowego zgodne z pamięcią podręczną trasy

## Wymagania

- PHP >= 8.2
- Laravel (podświetlenie) >= 9.x
- Lumen >= 9,x (opcjonalnie)

## Szybki start

### 1) Zainstaluj

```bash
composer require opgginc/laravel-mcp-server
```

### 2) Zarejestruj punkt końcowy (Laravel)

```php
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
        VersionCheckTool::class,
    ]);
```

### 3) Sprawdź

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

Szybka kontrola JSON-RPC:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Konfiguracja światła

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

## Minimalne bezpieczeństwo (produkcja)

Użyj oprogramowania pośredniczącego Laravel w grupie tras MCP.

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

## Uwagi dotyczące migracji do wersji 2.0.0 (z wersji 1.0.0)

- Konfiguracja punktu końcowego MCP została przeniesiona z konfiguracji do rejestracji trasy.
- Jedynym transportem jest przesyłany strumieniowo protokół HTTP.
- Mutatory metadanych serwera są konsolidowane w `setServerInfo(...)`.
- Polecenie migracji narzędzia jest dostępne dla starszych podpisów:

```bash
php artisan mcp:migrate-tools
```

Pełny przewodnik: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Zaawansowane funkcje (szybkie łącza)

- Utwórz narzędzia: `php artisan make:mcp-tool ToolName`
- Utwórz zasoby: `php artisan make:mcp-resource ResourceName`
- Utwórz szablony zasobów: `php artisan make:mcp-resource-template TemplateName`
- Utwórz podpowiedzi: `php artisan make:mcp-prompt PromptName`
- Utwórz powiadomienia: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- Wygeneruj z OpenAPI: `php artisan make:swagger-mcp-tool <spec-url-or-file>`

Odniesienia do kodu:
- Przykłady narzędzi: `src/Services/ToolService/Examples/`
- Przykłady zasobów: `src/Services/ResourceService/Examples/`
- Szybka obsługa: `src/Services/PromptService/`
- Obsługa powiadomień: `src/Server/Notification/`
- Kreator tras: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> Narzędzie MCP

Wygeneruj narzędzia MCP na podstawie specyfikacji Swagger/OpenAPI:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Przydatne opcje:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `path` lub `none`
- `--prefix`: przedrostek nazwy klasy dla wygenerowanych narzędzi/zasobów
- `--test-api`: przetestuj łączność z punktem końcowym przed generacją

Zachowanie pokolenia:
- W trybie interaktywnym możesz wybrać narzędzie lub zasób dla każdego punktu końcowego.
- W trybie nieinteraktywnym punkty końcowe `GET` są generowane jako zasoby, a inne metody jako narzędzia.

### Ulepszony interaktywny podgląd

Jeśli uruchomisz polecenie bez `--group-by`, generator wyświetli interaktywny podgląd struktury folderów i liczby plików przed utworzeniem.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Przykładowe wyjście podglądu:

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

Po wygenerowaniu zarejestruj wygenerowane klasy narzędzi na swoim punkcie końcowym MCP:

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

## Przykładowa klasa narzędzia

```php
<?php

namespace App\MCP\Tools;

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
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
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

## Przykładowa klasa podpowiedzi

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

## Przykładowa klasa zasobów

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

## Zarejestruj przykłady na trasie

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

## Polecenia dotyczące testowania i jakości

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Tłumaczenie

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Przetłumacz wybrane języki:

```bash
python scripts/translate_readme.py es ko
```

## Licencja

Projekt ten jest rozpowszechniany na licencji MIT.
