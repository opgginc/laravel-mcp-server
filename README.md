<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  A powerful Laravel package to build a Model Context Protocol Server seamlessly
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

## Overview

Laravel MCP Server is a powerful package designed to streamline the implementation of Model Context Protocol (MCP) servers in Laravel applications. **Unlike most Laravel MCP packages that use Standard Input/Output (stdio) transport**, this package focuses on **Streamable HTTP** transport and still includes a **legacy SSE provider** for backwards compatibility, providing a secure and controlled integration method.

### Why Streamable HTTP instead of STDIO?

While stdio is straightforward and widely used in MCP implementations, it has significant security implications for enterprise environments:

- **Security Risk**: STDIO transport potentially exposes internal system details and API specifications
- **Data Protection**: Organizations need to protect proprietary API endpoints and internal system architecture
- **Control**: Streamable HTTP offers better control over the communication channel between LLM clients and your application

By implementing the MCP server with Streamable HTTP transport, enterprises can:

- Expose only the necessary tools and resources while keeping proprietary API details private
- Maintain control over authentication and authorization processes

Key benefits:

- Seamless and rapid implementation of Streamable HTTP in existing Laravel projects
- Support for the latest Laravel and PHP versions
- Efficient server communication and real-time data processing
- Enhanced security for enterprise environments

## Key Features

- Real-time communication support through Streamable HTTP with SSE integration
- Implementation of tools and resources compliant with Model Context Protocol specifications
- Adapter-based design architecture with Pub/Sub messaging pattern (starting with Redis, more adapters planned)
- Simple routing and middleware configuration

### Transport Providers

The configuration option `server_provider` controls which transport is used. Available providers are:

1. **streamable_http** – the recommended default. Uses standard HTTP requests and avoids issues with platforms that close SSE connections after about a minute (e.g. many serverless environments).
2. **sse** – a legacy provider kept for backwards compatibility. It relies on long-lived SSE connections and may not work on platforms with short HTTP timeouts.

The MCP protocol also defines a "Streamable HTTP SSE" mode, but this package does not implement it and there are no plans to do so.

## Requirements

- PHP >=8.2
- Laravel >=10.x

## Installation

1. Install the package via Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Publish the configuration file:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Basic Usage

### Creating and Adding Custom Tools

The package provides convenient Artisan commands to generate new tools:

```bash
php artisan make:mcp-tool MyCustomTool
```

This command:

- Handles various input formats (spaces, hyphens, mixed case)
- Automatically converts the name to proper case format
- Creates a properly structured tool class in `app/MCP/Tools`
- Offers to automatically register the tool in your configuration

You can also manually create and register tools in `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Tool implementation
}
```

### Testing MCP Tools

The package includes a special command for testing your MCP tools without needing a real MCP client:

```bash
# Test a specific tool interactively
php artisan mcp:test-tool MyCustomTool

# List all available tools
php artisan mcp:test-tool --list

# Test with specific JSON input
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

This helps you rapidly develop and debug tools by:

- Showing the tool's input schema and validating inputs
- Executing the tool with your provided input
- Displaying formatted results or detailed error information
- Supporting complex input types including objects and arrays

### Visualizing MCP Tools with Inspector

You can also use the Model Context Protocol Inspector to visualize and test your MCP tools:

```bash
# Run the MCP Inspector without installation
npx @modelcontextprotocol/inspector node build/index.js
```

This will typically open a web interface at `localhost:6274`. To test your MCP server:

1. **Warning**: `php artisan serve` CANNOT be used with this package because it cannot handle multiple PHP connections simultaneously. Since MCP SSE requires processing multiple connections concurrently, you must use one of these alternatives:

   - **Laravel Octane** (Easiest option):

     ```bash
     # Install and set up Laravel Octane with FrankenPHP (recommended)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Start the Octane server
     php artisan octane:start
     ```

     > **Important**: When installing Laravel Octane, make sure to use FrankenPHP as the server. The package may not work properly with RoadRunner due to compatibility issues with SSE connections. If you can help fix this RoadRunner compatibility issue, please submit a Pull Request - your contribution would be greatly appreciated!

     For details, see the [Laravel Octane documentation](https://laravel.com/docs/12.x/octane)

   - **Production-grade options**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Custom Docker setup

   * Any web server that properly supports SSE streaming (required only for the legacy SSE provider)

2. In the Inspector interface, enter your Laravel server's MCP endpoint URL (e.g., `http://localhost:8000/mcp`). If you are using the legacy SSE provider, use the SSE URL instead (`http://localhost:8000/mcp/sse`).
3. Connect and explore available tools visually

The MCP endpoint follows the pattern: `http://[your-laravel-server]/[default_path]` where `default_path` is defined in your `config/mcp-server.php` file.

## Advanced Features

### Pub/Sub Architecture with SSE Adapters (legacy provider)

The package implements a publish/subscribe (pub/sub) messaging pattern through its adapter system:

1. **Publisher (Server)**: When clients send requests to the `/message` endpoint, the server processes these requests and publishes responses through the configured adapter.

2. **Message Broker (Adapter)**: The adapter (e.g., Redis) maintains message queues for each client, identified by unique client IDs. This provides a reliable asynchronous communication layer.

3. **Subscriber (SSE connection)**: Long-lived SSE connections subscribe to messages for their respective clients and deliver them in real-time. This applies only when using the legacy SSE provider.

This architecture enables:

- Scalable real-time communication
- Reliable message delivery even during temporary disconnections
- Efficient handling of multiple concurrent client connections
- Potential for distributed server deployments

### Redis Adapter Configuration

The default Redis adapter can be configured as follows:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefix for Redis keys
        'connection' => 'default', // Redis connection from database.php
        'ttl' => 100,              // Message TTL in seconds
    ],
],
```

## Environment Variables

The package supports the following environment variables to allow configuration without modifying the config files:

| Variable               | Description                             | Default   |
| ---------------------- | --------------------------------------- | --------- |
| `MCP_SERVER_ENABLED`   | Enable or disable the MCP server        | `true`    |
| `MCP_REDIS_CONNECTION` | Redis connection name from database.php | `default` |

### Example .env Configuration

```
# Disable MCP server in specific environments
MCP_SERVER_ENABLED=false

# Use a specific Redis connection for MCP
MCP_REDIS_CONNECTION=mcp
```

## Translation README.md

To translate this README to other languages using Claude API (Parallel processing):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

You can also translate specific languages:

```bash
python scripts/translate_readme.py es ko
```

## License

This project is distributed under the MIT license.
