<h1 align="center">Laravel MCP Server by OP.GG</h1>

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

Laravel MCP Server 是个很牛的包，可以让你在 Laravel 项目里轻松搭建 MCP 服务器。**不像其他大多数 Laravel MCP 包用的是 stdio**，这个包主推 **Streamable HTTP**，并保留了**旧版 SSE 提供者**以便向下兼容，从而提供更安全、更可控的集成方式。

### 为啥用 Streamable HTTP 而不用 STDIO？

Stdio 虽然简单，在 MCP 实现中也很常见，但在企业环境里会带来不少安全问题：

- **安全风险**：STDIO 传输可能会暴露内部系统细节和 API 规范
- **数据保护**：组织需要保护专有 API 端点和内部系统架构
- **控制能力**：Streamable HTTP 提供对 LLM 客户端与应用程序之间通信通道的更好控制

用 Streamable HTTP 来搭建 MCP 服务器，企业可以：

- 只开放必要的工具和资源，保护专有 API 的细节
- 简单管理认证和授权过程

最大优势：

- 快速易用，直接在现有 Laravel 项目里接入 Streamable HTTP
- 完全支持最新版 Laravel 和 PHP
- 运行高效，实时数据处理性能好
- 企业级安全性，更适合商业用途

## 主要特性

- 通过 Streamable HTTP 及旧版 SSE 实现实时通信
- 实现符合模型上下文协议规范的工具和资源
- 基于适配器的设计架构，采用发布/订阅消息模式（从 Redis 开始，计划添加更多适配器）
- 简单的路由和中间件配置

### 传输方式

在 `server_provider` 配置项中可以选择传输方式：

1. **streamable_http**：推荐使用的默认选项，采用普通 HTTP 请求，在许多 serverless 环境（如 Cloudflare）下也能正常工作，不受 SSE 60 秒超时限制。
2. **sse**：为兼容旧版而保留，依赖长连接 SSE，在部分平台上可能因超时而失败。

协议中还定义了 “Streamable HTTP SSE” 模式，但本库未实现，也暂无计划实现。

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

这个包提供了特别方便的 Artisan 命令来生成新工具：

```bash
php artisan make:mcp-tool MyCustomTool
```

这个命令能帮你：

- 处理各种输入格式（空格、连字符、大小写混用）
- 自动转换名称成正确的大小写格式
- 在 `app/MCP/Tools` 目录下创建一个结构良好的工具类
- 自动帮你把工具注册到配置文件中

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

### 用 MCP Inspector 可视化查看工具

你还可以用 MCP Inspector 来直观地查看和测试你的 MCP 工具：

```bash
# 不用安装，直接用 npx 运行
npx @modelcontextprotocol/inspector node build/index.js
```

这个命令会在 `localhost:6274` 打开一个可视化界面。要测试你的 MCP 服务器：

1. **警告**: 无法使用 `php artisan serve` 运行此包，因为它无法同时处理多个 PHP 连接。由于 MCP SSE 需要同时处理多个连接，你必须使用以下替代方案之一：

   * **Laravel Octane** (最简单选项):
     ```bash
     # 使用 FrankenPHP 安装和设置 Laravel Octane（推荐）
     composer require laravel/octane
     php artisan octane:install --server=frankenphp
     
     # 启动 Octane 服务器
     php artisan octane:start
     ```
     
     > **重要**: 安装 Laravel Octane 时，请确保使用 FrankenPHP 作为服务器。由于 SSE 连接兼容性问题，该包可能无法与 RoadRunner 正常工作。如果您能帮助解决这个 RoadRunner 兼容性问题，请提交 Pull Request - 非常感谢您的贡献！
     
     详细信息请参考 [Laravel Octane 文档](https://laravel.com/docs/12.x/octane)
     
   * **生产级选项**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 自定义 Docker 配置
    - 任何正确支持 SSE 流式传输的 Web 服务器（仅在使用旧版 SSE 提供者时需要）

2. 在 Inspector 界面中，输入 MCP 端点 URL（比如 `http://localhost:8000/mcp`）。如果使用旧版 SSE 提供者，则输入 SSE URL (`http://localhost:8000/mcp/sse`)。
3. 连接后就能直观地查看和测试所有工具了

MCP 端点的格式为：`http://[你的服务器地址]/[default_path]`，其中 `default_path` 在 `config/mcp-server.php` 文件里设置。

## 高级功能

### 带有 SSE 适配器的发布/订阅架构（仅旧版提供者）

该包通过其适配器系统实现发布/订阅（pub/sub）消息模式：

1. **发布者（服务器）**：当客户端向 `/message` 端点发送请求时，服务器处理这些请求并通过配置的适配器发布响应。

2. **消息代理（适配器）**：适配器（例如 Redis）为每个客户端维护消息队列，通过唯一的客户端 ID 识别。这提供了可靠的异步通信层。

3. **订阅者（SSE 连接）**：长期存在的 SSE 连接订阅各自客户端的消息并实时传递它们。此机制仅在使用旧版 SSE 提供者时适用。

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
