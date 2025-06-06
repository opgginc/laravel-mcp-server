<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  一个强大的 Laravel 扩展包，用于无缝构建模型上下文协议服务器
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">官方网站</a>
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

## ⚠️ v1.1.0 版本的重大变更

v1.1.0 版本对 `ToolInterface` 引入了重大的破坏性变更。如果你正在从 v1.0.x 升级，**必须**更新你的工具实现以符合新的接口。

**`ToolInterface` 的关键变更：**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` 已更新如下：

1.  **新增方法：**

    - `messageType(): ProcessMessageType`
      - 此方法对新的 HTTP 流支持至关重要，用于确定正在处理的消息类型。

2.  **方法重命名：**
    - `getName()` 现在是 `name()`
    - `getDescription()` 现在是 `description()`
    - `getInputSchema()` 现在是 `inputSchema()`
    - `getAnnotations()` 现在是 `annotations()`

**如何更新你的工具：**

### v1.1.0 自动化工具迁移

为了帮助过渡到 v1.1.0 中引入的新 `ToolInterface`，我们提供了一个 Artisan 命令来帮助自动重构你现有的工具：

```bash
php artisan mcp:migrate-tools {path?}
```

**功能说明：**

此命令将扫描指定目录中的 PHP 文件（默认为 `app/MCP/Tools/`）并尝试：

1.  **识别旧工具：** 查找实现了旧方法签名的 `ToolInterface` 的类。
2.  **创建备份：** 在进行任何更改之前，会创建原始工具文件的备份，扩展名为 `.backup`（例如 `YourTool.php.backup`）。如果备份文件已存在，将跳过原始文件以防止意外数据丢失。
3.  **重构工具：**
    - 重命名方法：
      - `getName()` 改为 `name()`
      - `getDescription()` 改为 `description()`
      - `getInputSchema()` 改为 `inputSchema()`
      - `getAnnotations()` 改为 `annotations()`
    - 添加新的 `messageType()` 方法，默认返回 `ProcessMessageType::SSE`。
    - 确保存在 `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` 语句。

**使用方法：**

将 `opgginc/laravel-mcp-server` 包更新到 v1.1.0 或更高版本后，如果你有为 v1.0.x 编写的现有工具，强烈建议运行此命令：

```bash
php artisan mcp:migrate-tools
```

如果你的工具位于 `app/MCP/Tools/` 以外的目录，可以指定路径：

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

该命令将输出其进度，指示正在处理、备份和迁移哪些文件。请始终检查工具所做的更改。虽然它力求准确，但复杂或格式异常的工具文件可能需要手动调整。

此工具应该大大简化迁移过程，帮助你快速适应新的接口结构。

### 手动迁移

如果你更喜欢手动迁移工具，这里有一个对比来帮助你适应现有工具：

**v1.0.x `ToolInterface`：**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getInputSchema(): array;
    public function getAnnotations(): array;
    public function execute(array $arguments): mixed;
}
```

**v1.1.0 `ToolInterface`（新版）：**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // 新方法
    public function name(): string;                     // 重命名
    public function description(): string;              // 重命名
    public function inputSchema(): array;               // 重命名
    public function annotations(): array;               // 重命名
    public function execute(array $arguments): mixed;   // 无变化
}
```

**更新后工具的示例：**

如果你的 v1.0.x 工具是这样的：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyOldTool implements ToolInterface
{
    public function getName(): string { return 'MyOldTool'; }
    public function getDescription(): string { return 'This is my old tool.'; }
    public function getInputSchema(): array { return []; }
    public function getAnnotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

你需要为 v1.1.0 更新如下：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // 导入枚举

class MyNewTool implements ToolInterface
{
    // 添加新的 messageType() 方法
    public function messageType(): ProcessMessageType
    {
        // 返回适当的消息类型，例如对于标准工具
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // 重命名
    public function description(): string { return 'This is my new tool.'; } // 重命名
    public function inputSchema(): array { return []; } // 重命名
    public function annotations(): array { return []; } // 重命名
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Laravel MCP Server 概述

Laravel MCP Server 是一个强大的扩展包，旨在简化在 Laravel 应用程序中实现模型上下文协议（MCP）服务器。**与大多数使用标准输入/输出（stdio）传输的 Laravel MCP 包不同**，此包专注于**可流式 HTTP** 传输，并仍包含**传统 SSE 提供程序**以实现向后兼容，提供安全且受控的集成方法。

### 为什么选择可流式 HTTP 而不是 STDIO？

虽然 stdio 简单直接且在 MCP 实现中广泛使用，但它对企业环境有重大的安全影响：

- **安全风险**：STDIO 传输可能暴露内部系统详细信息和 API 规范
- **数据保护**：组织需要保护专有 API 端点和内部系统架构
- **控制**：可流式 HTTP 在 LLM 客户端和你的应用程序之间的通信通道上提供更好的控制

通过使用可流式 HTTP 传输实现 MCP 服务器，企业可以：

- 仅暴露必要的工具和资源，同时保持专有 API 详细信息的私密性
- 保持对身份验证和授权过程的控制

主要优势：

- 在现有 Laravel 项目中无缝快速实现可流式 HTTP
- 支持最新的 Laravel 和 PHP 版本
- 高效的服务器通信和实时数据处理
- 为企业环境增强安全性

## 核心功能

- 通过可流式 HTTP 与 SSE 集成支持实时通信
- 实现符合模型上下文协议规范的工具和资源
- 基于适配器的设计架构，采用发布/订阅消息模式（从 Redis 开始，计划更多适配器）
- 简单的路由和中间件配置

### 传输提供程序

配置选项 `server_provider` 控制使用哪种传输。可用的提供程序有：

1. **streamable_http** – 推荐的默认选项。使用标准 HTTP 请求，避免在大约一分钟后关闭 SSE 连接的平台（例如许多无服务器环境）的问题。
2. **sse** – 为向后兼容而保留的传统提供程序。它依赖于长期存在的 SSE 连接，在 HTTP 超时较短的平台上可能无法工作。

MCP 协议还定义了"可流式 HTTP SSE"模式，但此包未实现它，也没有计划这样做。

## 系统要求

- PHP >=8.2
- Laravel >=10.x

## 安装

1. 通过 Composer 安装包：

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 发布配置文件：
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 基本用法

### 域名限制

你可以将 MCP 服务器路由限制到特定域名，以获得更好的安全性和组织性：

```php
// config/mcp-server.php

// 允许从所有域名访问（默认）
'domain' => null,

// 限制到单个域名
'domain' => 'api.example.com',

// 限制到多个域名
'domain' => ['api.example.com', 'admin.example.com'],
```

**何时使用域名限制：**
- 在不同子域上运行多个应用程序
- 将 API 端点与主应用程序分离
- 实现多租户架构，其中每个租户都有自己的子域
- 跨多个域名提供相同的 MCP 服务

**示例场景：**

```php
// 单个 API 子域
'domain' => 'api.op.gg',

// 不同环境的多个子域
'domain' => ['api.op.gg', 'staging-api.op.gg'],

// 多租户架构
'domain' => ['tenant1.op.gg', 'tenant2.op.gg', 'tenant3.op.gg'],

// 不同域名上的不同服务
'domain' => ['api.op.gg', 'api.kargn.as'],
```

> **注意：** 使用多个域名时，包会自动为每个域名注册单独的路由，以确保在所有指定域名上正确路由。

### 创建和添加自定义工具

包提供了便捷的 Artisan 命令来生成新工具：

```bash
php artisan make:mcp-tool MyCustomTool
```

此命令：

- 处理各种输入格式（空格、连字符、混合大小写）
- 自动将名称转换为正确的大小写格式
- 在 `app/MCP/Tools` 中创建结构正确的工具类
- 提供在配置中自动注册工具的选项

你也可以在 `config/mcp-server.php` 中手动创建和注册工具：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 工具实现
}
```

### 理解工具结构（ToolInterface）

当你通过实现 `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` 创建工具时，需要定义几个方法。以下是每个方法及其用途的详细说明：

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    // 确定工具消息的处理方式，通常与传输相关。
    public function messageType(): ProcessMessageType;

    // 工具的唯一可调用名称（例如 'get-user-details'）。
    public function name(): string;

    // 工具功能的人类可读描述。
    public function description(): string;

    // 使用类似 JSON Schema 的结构定义工具的预期输入参数。
    public function inputSchema(): array;

    // 提供向工具添加任意元数据或注释的方法。
    public function annotations(): array;

    // 工具的核心逻辑。接收验证过的参数并返回结果。
    public function execute(array $arguments): mixed;
}
```

让我们深入了解其中一些方法：

**`messageType(): ProcessMessageType`**

此方法指定工具的消息处理类型。它返回一个 `ProcessMessageType` 枚举值。可用类型有：

- `ProcessMessageType::HTTP`：用于通过标准 HTTP 请求/响应交互的工具。对于新工具最常见。
- `ProcessMessageType::SSE`：用于专门设计与服务器发送事件一起工作的工具。

对于大多数工具，特别是那些为主要的 `streamable_http` 提供程序设计的工具，你将返回 `ProcessMessageType::HTTP`。

**`name(): string`**

这是工具的标识符。它应该是唯一的。客户端将使用此名称来请求你的工具。例如：`get-weather`、`calculate-sum`。

**`description(): string`**

工具功能的清晰、简洁描述。这用于文档，MCP 客户端 UI（如 MCP Inspector）可能会向用户显示它。

**`inputSchema(): array`**

此方法对于定义工具的预期输入参数至关重要。它应该返回一个遵循类似 JSON Schema 结构的数组。此模式用于：

- 客户端了解要发送什么数据。
- 服务器或客户端可能用于输入验证。
- MCP Inspector 等工具生成测试表单。

**`inputSchema()` 示例：**

```php
public function inputSchema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'userId' => [
                'type' => 'integer',
                'description' => 'The unique identifier for the user.',
            ],
            'includeDetails' => [
                'type' => 'boolean',
                'description' => 'Whether to include extended details in the response.',
                'default' => false, // 你可以指定默认值
            ],
        ],
        'required' => ['userId'], // 指定哪些属性是必需的
    ];
}
```

在你的 `execute` 方法中，你可以验证传入的参数。`HelloWorldTool` 示例使用 `Illuminate\Support\Facades\Validator` 来做这件事：

```php
// 在你的 execute() 方法内：
$validator = Validator::make($arguments, [
    'userId' => ['required', 'integer'],
    'includeDetails' => ['sometimes', 'boolean'],
]);

if ($validator->fails()) {
    throw new JsonRpcErrorException(
        message: $validator->errors()->toJson(),
        code: JsonRpcErrorCode::INVALID_REQUEST
    );
}
// 继续使用验证过的 $arguments['userId'] 和 $arguments['includeDetails']
```

**`annotations(): array`**

此方法提供关于工具行为和特征的元数据，遵循官方的 [MCP 工具注释规范](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations)。注释帮助 MCP 客户端对工具进行分类，对工具批准做出明智决策，并提供适当的用户界面。

**标准 MCP 注释：**

模型上下文协议定义了几个客户端理解的标准注释：

- **`title`**（字符串）：工具的人类可读标题，显示在客户端 UI 中
- **`readOnlyHint`**（布尔值）：指示工具是否只读取数据而不修改环境（默认：false）
- **`destructiveHint`**（布尔值）：建议工具是否可能执行破坏性操作，如删除数据（默认：true）
- **`idempotentHint`**（布尔值）：指示使用相同参数的重复调用是否没有额外效果（默认：false）
- **`openWorldHint`**（布尔值）：表示工具是否与本地环境之外的外部实体交互（默认：true）

**重要：** 这些是提示，不是保证。它们帮助客户端提供更好的用户体验，但不应用于安全关键决策。

**带有标准 MCP 注释的示例：**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // 工具只读取用户数据
        'destructiveHint' => false,    // 工具不删除或修改数据
        'idempotentHint' => true,      // 多次调用是安全的
        'openWorldHint' => false,      // 工具只访问本地数据库
    ];
}
```

**按工具类型的实际示例：**

```php
// 数据库查询工具
public function annotations(): array
{
    return [
        'title' => 'Database Query Tool',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => false,
    ];
}

// 帖子删除工具
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // 可以删除帖子
        'idempotentHint' => false,     // 删除两次有不同效果
        'openWorldHint' => false,
    ];
}

// API 集成工具
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // 访问外部天气 API
    ];
}
```

**自定义注释**也可以为你的特定应用需求添加：

```php
public function annotations(): array
{
    return [
        // 标准 MCP 注释
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // 为你的应用程序自定义注释
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### 测试 MCP 工具

包包含一个特殊命令，用于测试你的 MCP 工具，无需真正的 MCP 客户端：

```bash
# 交互式测试特定工具
php artisan mcp:test-tool MyCustomTool

# 列出所有可用工具
php artisan mcp:test-tool --list

# 使用特定 JSON 输入测试
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

这通过以下方式帮助你快速开发和调试工具：

- 显示工具的输入模式并验证输入
- 使用你提供的输入执行工具
- 显示格式化的结果或详细的错误信息
- 支持复杂的输入类型，包括对象和数组

### 使用 Inspector 可视化 MCP 工具

你也可以使用模型上下文协议 Inspector 来可视化和测试你的 MCP 工具：

```bash
# 无需安装即可运行 MCP Inspector
npx @modelcontextprotocol/inspector node build/index.js
```

这通常会在 `localhost:6274` 打开一个 Web 界面。要测试你的 MCP 服务器：

1. **警告**：`php artisan serve` **不能**与此包一起使用，因为它无法同时处理多个 PHP 连接。由于 MCP SSE 需要并发处理多个连接，你必须使用以下替代方案之一：

   - **Laravel Octane**（最简单的选项）：

     ```bash
     # 安装并设置 Laravel Octane 与 FrankenPHP（推荐）
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # 启动 Octane 服务器
     php artisan octane:start
     ```

     > **重要**：安装 Laravel Octane 时，确保使用 FrankenPHP 作为服务器。由于与 SSE 连接的兼容性问题，该包可能无法与 RoadRunner 正常工作。如果你能帮助修复这个 RoadRunner 兼容性问题，请提交 Pull Request - 你的贡献将非常受欢迎！

     详情请参阅 [Laravel Octane 文档](https://laravel.com/docs/12.x/octane)

   - **生产级选项**：
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 自定义 Docker 设置

   * 任何正确支持 SSE 流的 Web 服务器（仅传统 SSE 提供程序需要）

2. 在 Inspector 界面中，输入你的 Laravel 服务器的 MCP 端点 URL（例如 `http://localhost:8000/mcp`）。如果你使用传统 SSE 提供程序，请改用 SSE URL（`http://localhost:8000/mcp/sse`）。
3. 连接并可视化地探索可用工具

MCP 端点遵循模式：`http://[your-laravel-server]/[default_path]`，其中 `default_path` 在你的 `config/mcp-server.php` 文件中定义。

## 高级功能

### 使用 SSE 适配器的发布/订阅架构（传统提供程序）

包通过其适配器系统实现发布/订阅（pub/sub）消息模式：

1. **发布者（服务器）**：当客户端向 `/message` 端点发送请求时，服务器处理这些请求并通过配置的适配器发布响应。

2. **消息代理（适配器）**：适配器（例如 Redis）为每个客户端维护消息队列，通过唯一的客户端 ID 标识。这提供了可靠的异步通信层。

3. **订阅者（SSE 连接）**：长期存在的 SSE 连接订阅其各自客户端的消息并实时传递它们。这仅在使用传统 SSE 提供程序时适用。

此架构实现：

- 可扩展的实时通信
- 即使在临时断开连接期间也能可靠地传递消息
- 高效处理多个并发客户端连接
- 分布式服务器部署的潜力

### Redis 适配器配置

默认的 Redis 适配器可以如下配置：

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Redis 键的前缀
        'connection' => 'default', // 来自 database.php 的 Redis 连接
        'ttl' => 100,              // 消息 TTL（秒）
    ],
],
```

## 翻译 README.md

使用 Claude API 将此 README 翻译成其他语言（并行处理）：

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

你也可以翻译特定语言：

```bash
python scripts/translate_readme.py es ko
```

## 许可证

此项目在 MIT 许可证下分发。