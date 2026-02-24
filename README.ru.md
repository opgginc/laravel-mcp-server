<h1 align="center">Сервер Laravel MCP от OP.GG</h1>

<p align="center">
  Создайте сервер MCP с приоритетом маршрута в Laravel и Lumen.
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Состояние сборки"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Всего загрузок"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Последняя стабильная версия"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Официальный сайт</a>
</p>

<p align="center">
  <a href="README.md">Английский</a> |
  <a href="README.pt-BR.md">Бразильский португальский</a> |
  <a href="README.ko.md">Корейский</a> |
  <a href="README.ru.md">Английский</a> |
  <a href="README.zh-CN.md">Упрощенный китайский</a> |
  <a href="README.zh-TW.md">Подробнее</a> |
  <a href="README.pl.md">Польский</a> |
  <a href="README.es.md">Испанский</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## Критические изменения 2.0.0

— Настройка конечной точки перенесена с регистрации на основе конфигурации на регистрацию на основе маршрута.
— Потоковый HTTP — единственный поддерживаемый транспорт.
— Мутаторы метаданных сервера объединены в `setServerInfo(...)`.
— Устаревшие методы транспортировки инструментов были удалены из среды выполнения (`messageType()`, `ProcessMessageType::SSE`).

Полное руководство по миграции: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Обзор

Сервер Laravel MCP обеспечивает регистрацию конечных точек MCP на основе маршрутов для Laravel и Lumen.

Ключевые моменты:
- Потоковая передача HTTP
- Конфигурация маршрутизации (`Route::mcp(...)` / `McpRoute::register(...)`)
- Инструмент, ресурс, шаблон ресурса и быстрая регистрация для каждой конечной точки.
- Метаданные конечной точки, совместимые с кэшем маршрутов.

## Требования

- PHP >= 8.2
- Laravel (Illuminate) >= 9.x
- Люмен >= 9.x (опционально)

## Быстрый старт

### 1) Установить

```bash
composer require opgginc/laravel-mcp-server
```

### 2) Зарегистрируйте конечную точку (Laravel)

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

### 3) Проверьте

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

Быстрая проверка JSON-RPC:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Настройка люмена

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

## Минимальная безопасность (Производство)

Используйте промежуточное программное обеспечение Laravel в своей группе маршрутов MCP.

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

## Примечания к миграции v2.0.0 (из версии 1.0.0)

— Настройка конечной точки MCP перенесена из конфигурации в регистрацию маршрута.
— Потоковый HTTP — единственный транспорт.
— Мутаторы метаданных сервера объединены в `setServerInfo(...)`.
- Команда миграции инструмента доступна для устаревших подписей:

```bash
php artisan mcp:migrate-tools
```

Полное руководство: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Расширенные функции (быстрые ссылки)

- Создайте инструменты: `php artisan make:mcp-tool ToolName`
- Создайте ресурсы: `php artisan make:mcp-resource ResourceName`
— Создайте шаблоны ресурсов: `php artisan make:mcp-resource-template TemplateName`
- Создайте подсказки: `php artisan make:mcp-prompt PromptName`
- Создание уведомлений: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- Генерировать из OpenAPI: `php artisan make:swagger-mcp-tool <spec-url-or-file>`

Ссылки на код:
- Примеры инструментов: `src/Services/ToolService/Examples/`
- Примеры ресурсов: `src/Services/ResourceService/Examples/`
- Оперативная служба: `src/Services/PromptService/`
- Обработчики уведомлений: `src/Server/Notification/`
- Построитель маршрутов: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> Инструмент MCP

Создайте инструменты MCP из спецификации Swagger/OpenAPI:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Полезные опции:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `path` или `none`
- `--prefix`: префикс имени класса для сгенерированных инструментов/ресурсов.
- `--test-api`: проверить подключение к конечной точке перед генерацией.

Поведение поколения:
- В интерактивном режиме вы можете выбрать инструмент или ресурс для каждой конечной точки.
— В неинтерактивном режиме конечные точки GET генерируются как ресурсы, а другие методы — как инструменты.

### Расширенный интерактивный предварительный просмотр

Если вы запустите команду без `--group-by`, генератор покажет интерактивный предварительный просмотр структуры папок и количества файлов перед созданием.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Пример вывода предварительного просмотра:

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

После создания зарегистрируйте сгенерированные классы инструментов на конечной точке MCP:

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

## Пример класса инструмента

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

## Пример класса подсказки

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

## Пример класса ресурса

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

## Регистрация примеров на маршруте

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

## Команды тестирования и качества

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Перевод

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Перевести выбранные языки:

```bash
python scripts/translate_readme.py es ko
```

## Лицензия

Этот проект распространяется по лицензии MIT.
