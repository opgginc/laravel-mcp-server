<h1align="center">OP.GG 的 Laravel MCP 伺服器</h1>

<p align="center">
  在 Laravel 和 Lumen 中建置路由優先的 MCP 伺服器
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="建置狀態"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="總下載量"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="最新穩定版本"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="許可證"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">官方網站</a>
</p>

<p align="center">
  <a href="README.md">簡體中文</a> |
  <a href="README.pt-BR.md">巴西葡萄牙語</a> |
  <a href="README.ko.md">韓文</a> |
  <a href="README.ru.md">英文</a> |
  <a href="README.zh-CN.md">簡體中文</a> |
  <a href="README.zh-TW.md">繁體中文</a> |
  <a href="README.pl.md">波蘭</a> |
  <a href="README.es.md">西班牙文</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## 重大變更 2.0.0

- 端點設定從設定驅動的註冊轉移到路由驅動的註冊。
- Streamable HTTP 是唯一支援的傳輸。
- 伺服器元資料變異器被合併到「setServerInfo(...)」。
- 舊工具傳輸方法已從執行時期刪除（`messageType()`、`ProcessMessageType::SSE`）。

完整遷移指南：[docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

＃＃ 概述

Laravel MCP Server 為 Laravel 和 Lumen 提供基於路由的 MCP 端點註冊。

要點：
- 可串流 HTTP 傳輸
- 路由優先配置（`Route::mcp(...)` / `McpRoute::register(...)`）
- 每個端點的工具、資源、資源範本和提示註冊
- 路由快取相容端點元數據

＃＃ 要求

- PHP >= 8.2
- Laravel（照亮）>= 9.x
- 流明 >= 9.x（可選）

## 快速入門

### 1) 安裝

```bash
composer require opgginc/laravel-mcp-server
```

### 2) 註冊端點 (Laravel)

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

### 3) 驗證

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

快速 JSON-RPC 檢查：

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## 流明設定

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

## 最低安全性（生產）

在 MCP 路由組上使用 Laravel 中介軟體。

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

## v2.0.0 遷移說明（從 v1.0.0 開始）

- MCP 端點設定從設定移至路由註冊。
- Streamable HTTP 是唯一的傳輸方式。
- 伺服器元資料變異器被合併到「setServerInfo(...)」。
- 工具遷移指令可用於舊簽章：

```bash
php artisan mcp:migrate-tools
```

完整指南：[docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## 進階功能（快速連結）

- 建立工具：`php artisan make:mcp-tool ToolName`
- 建立資源：`php artisan make:mcp-resource ResourceName`
- 建立資源模板：`php artisan make:mcp-resource-template TemplateName`
- 建立提示：`php artisan make:mcp-prompt PromptName`
- 建立通知：`php artisan make:mcp-notification HandlerName --method=notifications/method`
- 從 OpenAPI 產生：`php artisan make:swagger-mcp-tool <spec-url-or-file>`

代碼參考：
- 工具範例：`src/Services/ToolService/Examples/`
- 資源範例：`src/Services/ResourceService/Examples/`
- 提示服務：`src/Services/PromptService/`
- 通知處理程序：`src/Server/Notification/`
- 路由建構器：`src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> MCP 工具

從 Swagger/OpenAPI 規範產生 MCP 工具：

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

有用的選項：

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`：`標籤`、`路徑`或`無`
- `--prefix`：產生的工具/資源的類別名稱前綴
- `--test-api`：在生成之前測試端點連接

生成行為：
- 在互動模式下，您可以為每個端點選擇工具或資源。
- 在非互動模式下，「GET」端點會產生為資源，其他方法產生為工具。

### 增強的互動式預覽

如果執行不帶「--group-by」的命令，生成器會在建立之前顯示資料夾結構和檔案計數的互動式預覽。

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

預覽輸出範例：

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

產生後，在 MCP 端點上註冊產生的工具類別：

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

## 範例工具類

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

## 提示類別範例

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

## 範例資源類

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

## 在路由上註冊範例

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

## 測試和品質命令

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

＃＃ 翻譯

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

翻譯所選語言：

```bash
python scripts/translate_readme.py es ko
```

＃＃ 執照

該項目是根據 MIT 許可證分發的。
