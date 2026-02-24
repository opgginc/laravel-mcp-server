<h1align="center">OP.GG 的 Laravel MCP 服务器</h1>

<p align="center">
  在 Laravel 和 Lumen 中构建路由优先的 MCP 服务器
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="构建状态"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="总下载量"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="最新稳定版本"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="许可证"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">官方网站</a>
</p>

<p align="center">
  <a href="README.md">简体中文</a> |
  <a href="README.pt-BR.md">巴西葡萄牙语</a> |
  <a href="README.ko.md">韩语</a> |
  <a href="README.ru.md">英语</a> |
  <a href="README.zh-CN.md">简体中文</a> |
  <a href="README.zh-TW.md">繁体中文</a> |
  <a href="README.pl.md">波兰</a> |
  <a href="README.es.md">西班牙语</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## 重大变更 2.0.0

- 端点设置从配置驱动的注册转移到路由驱动的注册。
- Streamable HTTP 是唯一受支持的传输。
- 服务器元数据变异器被合并到“setServerInfo(...)”中。
- 旧工具传输方法已从运行时删除（`messageType()`、`ProcessMessageType::SSE`）。

完整迁移指南：[docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

＃＃ 概述

Laravel MCP Server 为 Laravel 和 Lumen 提供基于路由的 MCP 端点注册。

要点：
- 可流式 HTTP 传输
- 路由优先配置（`Route::mcp(...)` / `McpRoute::register(...)`）
- 每个端点的工具、资源、资源模板和提示注册
- 路由缓存兼容端点元数据

＃＃ 要求

- PHP >= 8.2
- Laravel（照亮）>= 9.x
- 流明 >= 9.x（可选）

## 快速入门

### 1) 安装

```bash
composer require opgginc/laravel-mcp-server
```

### 2) 注册端点 (Laravel)

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

### 3) 验证

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

快速 JSON-RPC 检查：

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## 流明设置

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

## 最低安全性（生产）

在 MCP 路由组上使用 Laravel 中间件。

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

## v2.0.0 迁移说明（从 v1.0.0 开始）

- MCP 端点设置从配置移至路由注册。
- Streamable HTTP 是唯一的传输方式。
- 服务器元数据变异器被合并到“setServerInfo(...)”中。
- 工具迁移命令可用于旧签名：

```bash
php artisan mcp:migrate-tools
```

完整指南：[docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## 高级功能（快速链接）

- 创建工具：`php artisan make:mcp-tool ToolName`
- 创建资源：`php artisan make:mcp-resource ResourceName`
- 创建资源模板：`php artisan make:mcp-resource-template TemplateName`
- 创建提示：`php artisan make:mcp-prompt PromptName`
- 创建通知：`php artisan make:mcp-notification HandlerName --method=notifications/method`
- 从 OpenAPI 生成：`php artisan make:swagger-mcp-tool <spec-url-or-file>`

代码参考：
- 工具示例：`src/Services/ToolService/Examples/`
- 资源示例：`src/Services/ResourceService/Examples/`
- 提示服务：`src/Services/PromptService/`
- 通知处理程序：`src/Server/Notification/`
- 路由构建器：`src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> MCP 工具

从 Swagger/OpenAPI 规范生成 MCP 工具：

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

有用的选项：

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`：`标签`、`路径`或`无`
- `--prefix`：生成的工具/资源的类名前缀
- `--test-api`：在生成之前测试端点连接

生成行为：
- 在交互模式下，您可以为每个端点选择工具或资源。
- 在非交互模式下，“GET”端点生成为资源，其他方法生成为工具。

### 增强的交互式预览

如果运行不带“--group-by”的命令，生成器会在创建之前显示文件夹结构和文件计数的交互式预览。

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

预览输出示例：

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

生成后，在 MCP 端点上注册生成的工具类：

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

## 示例工具类

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

## 提示类示例

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

## 示例资源类

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

## 在路由上注册示例

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

## 测试和质量命令

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

＃＃ 翻译

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

翻译所选语言：

```bash
python scripts/translate_readme.py es ko
```

＃＃ 执照

该项目是根据 MIT 许可证分发的。
