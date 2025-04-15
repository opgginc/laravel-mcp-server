<h1 align="center">OP.GG 的 Laravel MCP 服务器</h1>

<p align="center">
  一个强大的 Laravel 包，用于无缝构建模型上下文协议服务器
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="构建状态"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="总下载量"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="最新稳定版"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="许可证"></a>
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
  <a href="README.pl.md">Polski</a>
</p>

## 概述

Laravel MCP Server 是一个强大的包，旨在简化 Laravel 应用中模型上下文协议（MCP）服务器的实现。**与大多数使用标准输入/输出（stdio）传输的 Laravel MCP 包不同**，本包**利用服务器发送事件（SSE）传输**，提供更安全、更可控的集成方式。

### 为什么选择 SSE 而非 STDIO？

虽然 stdio 简单直接且在 MCP 实现中广泛使用，但在企业环境中存在显著的安全隐患：

- **安全风险**：STDIO 传输可能会暴露内部系统细节和 API 规范
- **数据保护**：组织需要保护专有 API 端点和内部系统架构
- **控制能力**：SSE 提供对 LLM 客户端与应用程序之间通信通道的更好控制

通过使用 SSE 传输实现 MCP 服务器，企业可以：

- 只暴露必要的工具和资源，同时保持专有 API 细节的私密性
- 保持对认证和授权过程的控制

主要优势：

- 在现有 Laravel 项目中无缝快速实现 SSE
- 支持最新的 Laravel 和 PHP 版本
- 高效的服务器通信和实时数据处理
- 为企业环境提供增强的安全性

## 主要特性

- 通过服务器发送事件（SSE）集成支持实时通信
- 实现符合模型上下文协议规范的工具和资源
- 基于适配器的设计架构，采用发布/订阅消息模式（从 Redis 开始，计划添加更多适配器）
- 简单的路由和中间件配置

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

### 创建和添加自定义工具

该包提供了便捷的 Artisan 命令来生成新工具：

```bash
php artisan make:mcp-tool MyCustomTool
```

此命令：

- 处理各种输入格式（空格、连字符、混合大小写）
- 自动将名称转换为适当的大小写格式
- 在 `app/MCP/Tools` 中创建结构良好的工具类
- 提供自动在配置中注册工具的选项

你也可以在 `config/mcp-server.php` 中手动创建和注册工具：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 工具实现
}
```

### 测试 MCP 工具

该包包含一个特殊命令，用于在不需要真实 MCP 客户端的情况下测试你的 MCP 工具：

```bash
# 交互式测试特定工具
php artisan mcp:test-tool MyCustomTool

# 列出所有可用工具
php artisan mcp:test-tool --list

# 使用特定 JSON 输入进行测试
php artisan mcp:test-tool MyCustomTool --input='{"param":"值"}'
```

这有助于你快速开发和调试工具：

- 显示工具的输入模式并验证输入
- 使用你提供的输入执行工具
- 显示格式化结果或详细错误信息
- 支持包括对象和数组在内的复杂输入类型

### 使用检查器可视化 MCP 工具

你还可以使用模型上下文协议检查器（Model Context Protocol Inspector）来可视化和测试你的 MCP 工具：

```bash
# 无需安装即可运行 MCP 检查器
npx @modelcontextprotocol/inspector node build/index.js
```

这通常会在 `localhost:6274` 打开一个网页界面。要测试你的 MCP 服务器：

1. 启动你的 Laravel 开发服务器（例如：`php artisan serve`）
2. 在检查器界面中，输入你的 Laravel 服务器的 MCP SSE URL（例如：`http://localhost:8000/mcp/sse`）
3. 连接并直观地探索可用工具

SSE URL 遵循以下模式：`http://[你的Laravel服务器]/[default_path]/sse`，其中 `default_path` 在你的 `config/mcp-server.php` 文件中定义。

## 高级功能

### 带有 SSE 适配器的发布/订阅架构

该包通过其适配器系统实现发布/订阅（pub/sub）消息模式：

1. **发布者（服务器）**：当客户端向 `/message` 端点发送请求时，服务器处理这些请求并通过配置的适配器发布响应。

2. **消息代理（适配器）**：适配器（例如 Redis）为每个客户端维护消息队列，通过唯一的客户端 ID 识别。这提供了可靠的异步通信层。

3. **订阅者（SSE 连接）**：长期存在的 SSE 连接订阅各自客户端的消息并实时传递它们。

这种架构实现了：

- 可扩展的实时通信
- 即使在临时断开连接期间也能可靠地传递消息
- 高效处理多个并发客户端连接
- 分布式服务器部署的潜力

### Redis 适配器配置

默认的 Redis 适配器可以按如下方式配置：

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Redis 键前缀
        'connection' => 'default', // 来自 database.php 的 Redis 连接
        'ttl' => 100,              // 消息 TTL（秒）
    ],
],
```

## 环境变量

该包支持以下环境变量，允许在不修改配置文件的情况下进行配置：

| 变量 | 描述 | 默认值 |
|----------|-------------|--------|
| `MCP_SERVER_ENABLED` | 启用或禁用 MCP 服务器 | `true` |
| `MCP_REDIS_CONNECTION` | 来自 database.php 的 Redis 连接名称 | `default` |

### .env 配置示例

```
# 在特定环境中禁用 MCP 服务器
MCP_SERVER_ENABLED=false

# 为 MCP 使用特定的 Redis 连接
MCP_REDIS_CONNECTION=mcp
```

## 许可证

本项目基于 MIT 许可证分发。
