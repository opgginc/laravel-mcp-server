<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
Construya un servidor MCP de ruta primero en Laravel y Lumen
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

## Cambios importantes 2.0.0

- La configuración de endpoints pasó del registro basado en configuración al registro basado en ruta.
- HTTP que se puede transmitir es el único transporte admitido.
- Los mutadores de metadatos del servidor se consolidan en `setServerInfo(...)`.
- Los métodos de transporte de herramientas heredados se eliminaron del tiempo de ejecución (`messageType()`, `ProcessMessageType::SSE`).

Guía de migración completa: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Descripción general

Laravel MCP Server proporciona registro de puntos finales MCP basado en rutas para Laravel y Lumen.

Puntos clave:
- Transporte HTTP transmitible
- Configuración de ruta primero (`Route::mcp(...)` / `McpRoute::register(...)`)
- Herramienta, recurso, plantilla de recurso y registro rápido por punto final
- Metadatos de punto final compatibles con caché de ruta

## Requisitos

- PHP >= 8.2
- Laravel (Iluminar) >= 9.x
- Lúmenes >= 9.x (opcional)

## Inicio rápido

### 1) instalar

```bash
composer require opgginc/laravel-mcp-server
```

### 2) Registrar un punto final (Laravel)

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

### 3) Verificar

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

Comprobación rápida de JSON-RPC:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Configuración de lúmenes

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

## Seguridad mínima (producción)

Utilice el middleware Laravel en su grupo de rutas MCP.

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

## Notas de migración v2.0.0 (desde v1.0.0)

- La configuración del punto final MCP pasó de la configuración al registro de ruta.
- HTTP transmitible es el único transporte.
- Los mutadores de metadatos del servidor se consolidan en `setServerInfo(...)`.
- El comando de migración de herramientas está disponible para firmas heredadas:

```bash
php artisan mcp:migrate-tools
```

Guía completa: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Funciones avanzadas (enlaces rápidos)

- Crear herramientas: `php artisan make:mcp-tool ToolName`
- Crear recursos: `php artisan make:mcp-resource ResourceName`
- Crear plantillas de recursos: `php artisan make:mcp-resource-template TemplateName`
- Crear indicaciones: `php artisan make:mcp-prompt PromptName`
- Crear notificaciones: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- Generar desde OpenAPI: `php artisan make:swagger-mcp-tool <spec-url-or-file>`

Referencias de código:
- Ejemplos de herramientas: `src/Services/ToolService/Examples/`
- Ejemplos de recursos: `src/Services/ResourceService/Examples/`
- Servicio rápido: `src/Services/PromptService/`
- Controladores de notificaciones: `src/Server/Notification/`
- Generador de rutas: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> Herramienta MCP

Genere herramientas MCP a partir de una especificación Swagger/OpenAPI:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Opciones útiles:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `path` o `none`
- `--prefix`: prefijo de nombre de clase para herramientas/recursos generados
- `--test-api`: prueba la conectividad del punto final antes de la generación

Comportamiento generacional:
- En modo interactivo, puede elegir Herramienta o Recurso por punto final.
- En modo no interactivo, los puntos finales `GET` se generan como Recursos y otros métodos como Herramientas.

### Vista previa interactiva mejorada

Si ejecuta el comando sin `--group-by`, el generador muestra una vista previa interactiva de la estructura de carpetas y el recuento de archivos antes de la creación.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Ejemplo de salida de vista previa:

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

Después de la generación, registre las clases de herramientas generadas en su punto final MCP:

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

## Clase de herramienta de ejemplo

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

## Clase de mensaje de ejemplo

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

## Clase de recurso de ejemplo

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

## Registrar ejemplos en una ruta

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

## Comandos de prueba y calidad

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Traducción

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Traducir idiomas seleccionados:

```bash
python scripts/translate_readme.py es ko
```

## Licencia

Este proyecto se distribuye bajo la licencia MIT.
