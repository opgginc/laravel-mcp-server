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

## Overview

Laravel MCP Server is a powerful package designed to streamline the implementation of Model Context Protocol (MCP) servers in Laravel applications. **Unlike most Laravel MCP packages that use Standard Input/Output (stdio) transport**, this package **utilizes Server-Sent Events (SSE)** transport, providing a more secure and controlled integration method.

### Why SSE instead of STDIO?

While stdio is straightforward and widely used in MCP implementations, it has significant security implications for enterprise environments:

- **Security Risk**: STDIO transport potentially exposes internal system details and API specifications
- **Data Protection**: Organizations need to protect proprietary API endpoints and internal system architecture
- **Control**: SSE offers better control over the communication channel between LLM clients and your application

By implementing the MCP server with SSE transport, enterprises can:

- Expose only the necessary tools and resources while keeping proprietary API details private
- Maintain control over authentication and authorization processes

Key benefits:

- Seamless and rapid implementation of SSE in existing Laravel projects
- Support for the latest Laravel and PHP versions
- Efficient server communication and real-time data processing
- Enhanced security for enterprise environments

## Key Features

- Real-time communication support through Server-Sent Events (SSE) integration
- Implementation of tools and resources compliant with Model Context Protocol specifications
- Adapter-based design architecture with Pub/Sub messaging pattern (starting with Redis, more adapters planned)
- Simple routing and middleware configuration

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

### Adding Custom Tools

To develop your own tools, create a new class and register it in `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\BaseTool;

class MyCustomTool extends BaseTool
{
    // Tool implementation
}
```

## Advanced Features

### Pub/Sub Architecture with SSE Adapters

The package implements a publish/subscribe (pub/sub) messaging pattern through its adapter system:

1. **Publisher (Server)**: When clients send requests to the `/message` endpoint, the server processes these requests and publishes responses through the configured adapter.

2. **Message Broker (Adapter)**: The adapter (e.g., Redis) maintains message queues for each client, identified by unique client IDs. This provides a reliable asynchronous communication layer.

3. **Subscriber (SSE Connection)**: Long-lived SSE connections subscribe to messages for their respective clients and deliver them in real-time.

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

## Contributing

Bug reports and feature improvement suggestions can be submitted through the GitHub repository. For detailed information on how to contribute, please refer to the CONTRIBUTING.md file.

## License

This project is distributed under the MIT license.
