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

## ⚠️ Breaking Changes in v1.1.0

Version 1.1.0 introduced a significant and breaking change to the `ToolInterface`. If you are upgrading from v1.0.x, you **must** update your tool implementations to conform to the new interface.

**Key Changes in `ToolInterface`:**

The `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` has been updated as follows:

1.  **New Method Added:**

    - `messageType(): ProcessMessageType`
      - This method is crucial for the new HTTP stream support and determines the type of message being processed.

2.  **Method Renames:**
    - `getName()` is now `name()`
    - `getDescription()` is now `description()`
    - `getInputSchema()` is now `inputSchema()`
    - `getAnnotations()` is now `annotations()`

**How to Update Your Tools:**

### Automated Tool Migration for v1.1.0

To assist with the transition to the new `ToolInterface` introduced in v1.1.0, we've included an Artisan command that can help automate the refactoring of your existing tools:

```bash
php artisan mcp:migrate-tools {path?}
```

**What it does:**

This command will scan PHP files in the specified directory (defaults to `app/MCP/Tools/`) and attempt to:

1.  **Identify old tools:** It looks for classes implementing the `ToolInterface` with the old method signatures.
2.  **Create Backups:** Before making any changes, it will create a backup of your original tool file with a `.backup` extension (e.g., `YourTool.php.backup`). If a backup file already exists, the original file will be skipped to prevent accidental data loss.
3.  **Refactor the Tool:**
    - Rename methods:
      - `getName()` to `name()`
      - `getDescription()` to `description()`
      - `getInputSchema()` to `inputSchema()`
      - `getAnnotations()` to `annotations()`
    - Add the new `messageType()` method, which will default to returning `ProcessMessageType::SSE`.
    - Ensure the `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` statement is present.

**Usage:**

After updating the `opgginc/laravel-mcp-server` package to v1.1.0 or later, if you have existing tools written for v1.0.x, it is highly recommended to run this command:

```bash
php artisan mcp:migrate-tools
```

If your tools are located in a directory other than `app/MCP/Tools/`, you can specify the path:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

The command will output its progress, indicating which files are being processed, backed up, and migrated. Always review the changes made by the tool. While it aims to be accurate, complex or unusually formatted tool files might require manual adjustments.

This tool should significantly ease the migration process and help you adapt to the new interface structure quickly.

### Manual Migration

If you prefer to migrate your tools manually, here's a comparison to help you adapt your existing tools:

**v1.0.x `ToolInterface`:**

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

**v1.1.0 `ToolInterface` (New):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // New method
    public function name(): string;                     // Renamed
    public function description(): string;              // Renamed
    public function inputSchema(): array;               // Renamed
    public function annotations(): array;               // Renamed
    public function execute(array $arguments): mixed;   // No change
}
```

**Example of an updated tool:**

If your v1.0.x tool looked like this:

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

You need to update it for v1.1.0 as follows:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // Import the enum

class MyNewTool implements ToolInterface
{
    // Add the new messageType() method
    public function messageType(): ProcessMessageType
    {
        // Return the appropriate message type, e.g., for a standard tool
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // Renamed
    public function description(): string { return 'This is my new tool.'; } // Renamed
    public function inputSchema(): array { return []; } // Renamed
    public function annotations(): array { return []; } // Renamed
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Overview of Laravel MCP Server

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

### Domain Restriction

You can restrict MCP server routes to specific domain(s) for better security and organization:

```php
// config/mcp-server.php

// Allow access from all domains (default)
'domain' => null,

// Restrict to a single domain
'domain' => 'api.example.com',

// Restrict to multiple domains
'domain' => ['api.example.com', 'admin.example.com'],
```

**When to use domain restriction:**
- Running multiple applications on different subdomains
- Separating API endpoints from your main application
- Implementing multi-tenant architectures where each tenant has its own subdomain
- Providing the same MCP services across multiple domains

**Example scenarios:**

```php
// Single API subdomain
'domain' => 'api.op.gg',

// Multiple subdomains for different environments
'domain' => ['api.op.gg', 'staging-api.op.gg'],

// Multi-tenant architecture
'domain' => ['tenant1.op.gg', 'tenant2.op.gg', 'tenant3.op.gg'],

// Different services on different domains
'domain' => ['api.op.gg', 'api.kargn.as'],
```

> **Note:** When using multiple domains, the package automatically registers separate routes for each domain to ensure proper routing across all specified domains.

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

### Understanding Your Tool's Structure (ToolInterface)

When you create a tool by implementing `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, you'll need to define several methods. Here's a breakdown of each method and its purpose:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    // Determines how the tool's messages are processed, often related to the transport.
    public function messageType(): ProcessMessageType;

    // The unique, callable name of your tool (e.g., 'get-user-details').
    public function name(): string;

    // A human-readable description of what your tool does.
    public function description(): string;

    // Defines the expected input parameters for your tool using a JSON Schema-like structure.
    public function inputSchema(): array;

    // Provides a way to add arbitrary metadata or annotations to your tool.
    public function annotations(): array;

    // The core logic of your tool. Receives validated arguments and returns the result.
    public function execute(array $arguments): mixed;
}
```

Let's dive deeper into some of these methods:

**`messageType(): ProcessMessageType`**

This method specifies the type of message processing for your tool. It returns a `ProcessMessageType` enum value. The available types are:

- `ProcessMessageType::HTTP`: For tools interacting via standard HTTP request/response. Most common for new tools.
- `ProcessMessageType::SSE`: For tools specifically designed to work with Server-Sent Events.

For most tools, especially those designed for the primary `streamable_http` provider, you'll return `ProcessMessageType::HTTP`.

**`name(): string`**

This is the identifier for your tool. It should be unique. Clients will use this name to request your tool. For example: `get-weather`, `calculate-sum`.

**`description(): string`**

A clear, concise description of your tool's functionality. This is used in documentation, and MCP client UIs (like the MCP Inspector) may display it to users.

**`inputSchema(): array`**

This method is crucial for defining your tool's expected input parameters. It should return an array that follows a structure similar to JSON Schema. This schema is used:

- By clients to understand what data to send.
- Potentially by the server or client for input validation.
- By tools like the MCP Inspector to generate forms for testing.

**Example `inputSchema()`:**

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
                'default' => false, // You can specify default values
            ],
        ],
        'required' => ['userId'], // Specifies which properties are mandatory
    ];
}
```

In your `execute` method, you can then validate the incoming arguments. The `HelloWorldTool` example uses `Illuminate\Support\Facades\Validator` for this:

```php
// Inside your execute() method:
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
// Proceed with validated $arguments['userId'] and $arguments['includeDetails']
```

**`annotations(): array`**

This method provides metadata about your tool's behavior and characteristics, following the official [MCP Tool Annotations specification](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Annotations help MCP clients categorize tools, make informed decisions about tool approval, and provide appropriate user interfaces.

**Standard MCP Annotations:**

The Model Context Protocol defines several standard annotations that clients understand:

- **`title`** (string): A human-readable title for the tool, displayed in client UIs
- **`readOnlyHint`** (boolean): Indicates if the tool only reads data without modifying the environment (default: false)
- **`destructiveHint`** (boolean): Suggests if the tool may perform destructive operations like deleting data (default: true)
- **`idempotentHint`** (boolean): Indicates if repeated calls with the same arguments have no additional effect (default: false)
- **`openWorldHint`** (boolean): Signals if the tool interacts with external entities beyond the local environment (default: true)

**Important:** These are hints, not guarantees. They help clients provide better user experiences but should not be used for security-critical decisions.

**Example with standard MCP annotations:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // Tool only reads user data
        'destructiveHint' => false,    // Tool doesn't delete or modify data
        'idempotentHint' => true,      // Safe to call multiple times
        'openWorldHint' => false,      // Tool only accesses local database
    ];
}
```

**Real-world examples by tool type:**

```php
// Database query tool
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

// Post deletion tool
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Can delete posts
        'idempotentHint' => false,     // Deleting twice has different effects
        'openWorldHint' => false,
    ];
}

// API integration tool
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Accesses external weather API
    ];
}
```

**Custom annotations** can also be added for your specific application needs:

```php
public function annotations(): array
{
    return [
        // Standard MCP annotations
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // Custom annotations for your application
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Working with Resources

Resources expose data from your server that can be read by MCP clients. They are
**application-controlled**, meaning the client decides when and how to use them.
Create concrete resources or URI templates in `app/MCP/Resources` and
`app/MCP/ResourceTemplates` using the Artisan helpers:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

Register the generated classes in `config/mcp-server.php` under the `resources`
and `resource_templates` arrays. Each resource class extends the base
`Resource` class and implements a `read()` method that returns either `text` or
`blob` content. Templates extend `ResourceTemplate` and describe dynamic URI
patterns clients can use. A resource is identified by a URI such as
`file:///logs/app.log` and may optionally define metadata like `mimeType` or
`size`.

List available resources using the `resources/list` endpoint and read their
contents with `resources/read`. The `resources/list` endpoint returns both
concrete resources and resource templates in a single response:

```json
{
  "resources": [...],          // Array of concrete resources
  "resourceTemplates": [...]   // Array of URI templates
}
```

Resource templates allow clients to construct dynamic resource identifiers
using URI templates (RFC 6570). You can also list templates separately using
the `resources/templates/list` endpoint:

```bash
# List only resource templates
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

When running your Laravel MCP server remotely, the HTTP transport works with
standard JSON-RPC requests. Here is a simple example using `curl` to list and
read resources:

```bash
# List resources
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# Read a specific resource
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

The server responds with JSON messages streamed over the HTTP connection, so
`curl --no-buffer` can be used if you want to see incremental output.

### Working with Prompts

Prompts provide reusable text snippets with argument support that your tools or users can request.
Create prompt classes in `app/MCP/Prompts` using:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

Register them in `config/mcp-server.php` under `prompts`. Each prompt class
extends the `Prompt` base class and defines:
- `name`: Unique identifier (e.g., "welcome-user")
- `description`: Optional human-readable description  
- `arguments`: Array of argument definitions with name, description, and required fields
- `text`: The prompt template with placeholders like `{username}`

List prompts via the `prompts/list` endpoint and fetch them using
`prompts/get` with arguments:

```bash
# Fetch a welcome prompt with arguments
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### MCP Prompts

When crafting prompts that reference your tools or resources, consult the [official prompt guidelines](https://modelcontextprotocol.io/docs/concepts/prompts). Prompts are reusable templates that can accept arguments, include resource context and even describe multi-step workflows.

**Prompt structure**

```json
{
  "name": "string",
  "description": "string",
  "arguments": [
    {
      "name": "string",
      "description": "string",
      "required": true
    }
  ]
}
```

Clients discover prompts via `prompts/list` and request specific ones with `prompts/get`:

```json
{
  "method": "prompts/get",
  "params": {
    "name": "analyze-code",
    "arguments": {
      "language": "php"
    }
  }
}
```

**Example Prompt Class**

```php
use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';
    
    public ?string $description = 'A customizable welcome message for users';
    
    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'The name of the user to welcome',
            'required' => true,
        ],
        [
            'name' => 'role',
            'description' => 'The role of the user (optional)',
            'required' => false,
        ],
    ];
    
    public string $text = 'Welcome, {username}! You are logged in as {role}.';
}
```

Prompts can embed resources and return sequences of messages to guide an LLM. See the official documentation for advanced examples and best practices.


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
