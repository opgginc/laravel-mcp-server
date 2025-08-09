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

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## ⚠️ Version Information & Breaking Changes

### v1.4.0 Changes (Latest) 🚀

Version 1.4.0 introduces powerful automatic tool and resource generation from Swagger/OpenAPI specifications:

**New Features:**
- **Swagger/OpenAPI Tool & Resource Generator**: Automatically generate MCP tools or resources from any Swagger/OpenAPI specification
  - Supports both OpenAPI 3.x and Swagger 2.0 formats
  - **Choose generation type**: Generate as Tools (for actions) or Resources (for read-only data)
  - Interactive endpoint selection with grouping options
  - Automatic authentication logic generation (API Key, Bearer Token, OAuth2)
  - Smart naming for readable class names (handles hash-based operationIds)
  - Built-in API testing before generation
  - Complete Laravel HTTP client integration with retry logic

**Example Usage:**
```bash
# Generate tools from OP.GG API
php artisan make:swagger-mcp-tool https://api.op.gg/lol/swagger.json

# With options
php artisan make:swagger-mcp-tool ./api-spec.json --test-api --group-by=tag --prefix=MyApi
```

This feature dramatically reduces the time needed to integrate external APIs into your MCP server!

### v1.3.0 Changes

Version 1.3.0 introduces improvements to the `ToolInterface` for better communication control:

**New Features:**
- Added `isStreaming(): bool` method for clearer communication pattern selection
- Improved migration tools supporting upgrades from v1.1.x, v1.2.x to v1.3.0
- Enhanced stub files with comprehensive v1.3.0 documentation

**Deprecated Features:**
- `messageType(): ProcessMessageType` method is now deprecated (will be removed in v2.0.0)
- Use `isStreaming(): bool` instead for better clarity and simplicity

### Breaking Changes in v1.1.0 (May 2025)

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
    /**
     * @deprecated since v1.3.0, use isStreaming() instead. Will be removed in v2.0.0
     */
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function isStreaming(): bool
    {
        return false; // Most tools should return false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
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

### 🔐 Authentication (CRITICAL FOR PRODUCTION)

> **⚠️ SECURITY WARNING:** Authentication is **ESSENTIAL** for production deployments. Without proper authentication, your MCP server endpoints are publicly accessible, potentially exposing sensitive data and operations.

The Laravel MCP Server uses Laravel's middleware system for authentication, providing flexibility to implement various authentication strategies. **By default, NO authentication is enabled** - you MUST configure it for production use.

#### Quick Start: Securing Your MCP Server

##### 1. Enable Authentication in Configuration

Edit your `config/mcp-server.php` file to add authentication middleware:

```php
// config/mcp-server.php

'middlewares' => [
    // PRODUCTION CONFIGURATION (Choose one or combine):
    'auth:sanctum',      // For Laravel Sanctum (recommended)
    // 'auth:api',        // For Laravel Passport
    // 'custom.mcp.auth', // For custom authentication
    'throttle:100,1',     // Rate limiting (100 requests per minute)
    'cors',               // CORS support if needed
],
```

##### 2. Option A: Laravel Sanctum (Recommended)

**Installation and Setup:**

```bash
# Install Sanctum
composer require laravel/sanctum

# Publish configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate
```

**Generate API Tokens for MCP Clients:**

```php
// In your application code or tinker
$user = User::find(1);
$token = $user->createToken('MCP Client')->plainTextToken;

// Use this token in your MCP client configuration
```

**Client Usage:**

```bash
# Include the Bearer token in your requests
curl -X POST http://your-server.com/mcp \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

##### 3. Option B: Custom API Key Authentication

**Create Custom Middleware:**

```php
// app/Http/Middleware/McpApiKeyAuth.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class McpApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-MCP-API-Key');
        
        // Validate against environment variable or database
        if ($apiKey !== config('mcp.api_key')) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32001,
                    'message' => 'Unauthorized: Invalid API key'
                ]
            ], 401);
        }
        
        return $next($request);
    }
}
```

**Register the Middleware:**

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    // ... other middleware
    'mcp.auth' => \App\Http\Middleware\McpApiKeyAuth::class,
];
```

**Configure in MCP Settings:**

```php
// config/mcp-server.php
'middlewares' => [
    'mcp.auth',        // Your custom API key middleware
    'throttle:100,1',  // Rate limiting
],

// .env file
MCP_API_KEY=your-secure-api-key-here
```

#### Advanced Security Configurations

##### IP Whitelisting

Restrict access to specific IP addresses:

```php
// app/Http/Middleware/McpIpWhitelist.php
class McpIpWhitelist
{
    public function handle(Request $request, Closure $next)
    {
        $allowedIps = config('mcp.allowed_ips', []);
        
        if (!empty($allowedIps) && !in_array($request->ip(), $allowedIps)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32004,
                    'message' => 'Access denied from this IP address'
                ]
            ], 403);
        }
        
        return $next($request);
    }
}
```

##### Role-Based Access Control (RBAC)

Control access to specific tools based on user roles:

```php
// app/Http/Middleware/McpRoleAuth.php
class McpRoleAuth
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = auth()->user();
        
        if (!$user || !$user->hasRole($role)) {
            return response()->json([
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32003,
                    'message' => 'Forbidden: Insufficient permissions'
                ]
            ], 403);
        }
        
        return $next($request);
    }
}
```

##### Audit Logging

Track all MCP requests for security monitoring:

```php
// app/Http/Middleware/McpAuditLog.php
class McpAuditLog
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        Log::channel('mcp_audit')->info('MCP Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'payload' => $request->json()->all(),
            'status' => $response->getStatusCode(),
            'timestamp' => now(),
        ]);
        
        return $response;
    }
}
```

#### Environment-Specific Configuration

Configure different authentication strategies per environment:

```php
// config/mcp-server.php
'middlewares' => array_filter([
    // Always apply rate limiting
    'throttle:' . env('MCP_RATE_LIMIT', '60') . ',1',
    
    // Authentication only in non-local environments
    env('APP_ENV') !== 'local' ? 'auth:sanctum' : null,
    
    // IP whitelisting for production
    env('APP_ENV') === 'production' ? 'mcp.ip.whitelist' : null,
    
    // Audit logging for production
    env('APP_ENV') === 'production' ? 'mcp.audit' : null,
    
    // CORS if needed
    env('MCP_CORS_ENABLED', false) ? 'cors' : null,
]),
```

#### Testing Authentication

Verify your authentication is working correctly:

```bash
# Test without authentication (should fail)
curl -X POST http://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
# Expected: 401 Unauthorized

# Test with valid authentication
curl -X POST http://your-server.com/mcp \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
# Expected: 200 OK with tools list

# Test rate limiting (make multiple rapid requests)
for i in {1..101}; do
  curl -X POST http://your-server.com/mcp \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"jsonrpc":"2.0","id":'$i',"method":"ping"}'
done
# Expected: 429 Too Many Requests after limit exceeded
```

#### Security Best Practices

1. **Never expose MCP endpoints without authentication in production**
2. **Use HTTPS exclusively** - Never send authentication tokens over HTTP
3. **Implement rate limiting** to prevent abuse
4. **Rotate API keys/tokens regularly**
5. **Monitor and audit** all MCP requests
6. **Use environment variables** for sensitive configuration
7. **Implement IP whitelisting** for additional security when possible
8. **Consider OAuth2** for third-party integrations
9. **Test your authentication** thoroughly before deployment
10. **Document your authentication** method for your team

#### Common Authentication Patterns

##### Internal Services
For microservices or internal tools:
```php
'middlewares' => [
    'mcp.api.key',      // Simple API key
    'mcp.ip.whitelist', // Restrict to internal IPs
    'throttle:1000,1',  // Higher rate limit for internal use
]
```

##### Public API with User Authentication
For user-facing applications:
```php
'middlewares' => [
    'auth:sanctum',     // User authentication
    'throttle:60,1',    // Stricter rate limiting
    'cors',             // CORS for web clients
    'mcp.audit',        // Audit all requests
]
```

##### Partner Integration
For third-party integrations:
```php
'middlewares' => [
    'auth:api',         // OAuth2 via Passport
    'throttle:100,1',   // Moderate rate limiting
    'mcp.partner.acl',  // Partner-specific access control
    'mcp.audit',        // Full audit trail
]
```

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

#### Generate Tools from Swagger/OpenAPI Specifications (v1.4.0+)

Automatically generate MCP tools from any Swagger/OpenAPI specification with a single command:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/swagger.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json

# With options
php artisan make:swagger-mcp-tool https://api.example.com/swagger.json \
  --test-api \
  --group-by=tag \
  --prefix=MyApi
```

**Real-world Example with OP.GG API:**

```bash
➜ php artisan make:swagger-mcp-tool https://api.op.gg/lol/swagger.json

🚀 Swagger/OpenAPI to MCP Generator
=========================================
📄 Loading spec from: https://api.op.gg/lol/swagger.json
✅ Spec loaded successfully!
+-----------------+-------------------------+
| Property        | Value                   |
+-----------------+-------------------------+
| Title           | OP.GG Api Documentation |
| Version         | openapi-3.0.0           |
| Base URL        | https://api.op.gg      |
| Total Endpoints | 6                       |
| Tags            | Riot                    |
| Security        |                         |
+-----------------+-------------------------+

🎯 What would you like to generate from this API?

Tools: For operations that perform actions (create, update, delete, compute)
Resources: For read-only data endpoints that provide information

Generate as:
  [0] Tools (for actions)
  > 1
  [1] Resources (for read-only data)
  > 1

✓ Will generate as MCP Resources

Would you like to modify the base URL? Current: https://api.op.gg (yes/no) [no]:
> no

📋 Select endpoints to generate resources for:
Note: Only GET endpoints can be converted to resources
Include tag: Riot (6 endpoints)? (yes/no) [yes]:
> yes

Selected 6 endpoints.
🛠️ Generating MCP resources...
Note: operationId '5784a7dfd226e1621b0e6ee8c4f39407' looks like a hash, will use path-based naming
Generating: LolRegionRankingsGameTypeResource
  ✅ Generated: LolRegionRankingsGameTypeResource
Generating: LolRegionServerStatsResource
  ✅ Generated: LolRegionServerStatsResource
...

📦 Generated 6 MCP resources:
  - LolRegionRankingsGameTypeResource
  - LolRegionServerStatsResource
  - LolMetaChampionsResource
  ...

✅ MCP resources generated successfully!
```

**Key Features:**
- **Automatic API parsing**: Supports OpenAPI 3.x and Swagger 2.0 specifications
- **Dual generation modes**: 
  - **Tools**: For operations that perform actions (POST, PUT, DELETE, etc.)
  - **Resources**: For read-only GET endpoints that provide data
- **Smart naming**: Converts paths like `/lol/{region}/server-stats` to `LolRegionServerStatsTool` or `LolRegionServerStatsResource`
- **Hash detection**: Automatically detects MD5-like operationIds and uses path-based naming instead
- **Interactive mode**: Select which endpoints to convert
- **API testing**: Test API connectivity before generating
- **Authentication support**: Automatically generates authentication logic for API Key, Bearer Token, and OAuth2
- **Smart grouping**: Group endpoints by tags or path prefixes
- **Code generation**: Creates ready-to-use classes with Laravel HTTP client integration

The generated tools include:
- Proper input validation based on API parameters
- Authentication headers configuration
- Error handling for API responses with JsonRpcErrorException
- Request retry logic (3 retries with 100ms delay)
- Query parameter, path parameter, and request body handling
- Laravel HTTP client with timeout configuration

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
    /**
     * @deprecated since v1.3.0, use isStreaming() instead. Will be removed in v2.0.0
     */
    public function messageType(): ProcessMessageType;

    // NEW in v1.3.0: Determines if this tool requires streaming (SSE) instead of standard HTTP.
    public function isStreaming(): bool;

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

**`messageType(): ProcessMessageType` (Deprecated in v1.3.0)**

⚠️ **This method is deprecated since v1.3.0.** Use `isStreaming(): bool` instead for better clarity.

This method specifies the type of message processing for your tool. It returns a `ProcessMessageType` enum value. The available types are:

- `ProcessMessageType::HTTP`: For tools interacting via standard HTTP request/response. Most common for new tools.
- `ProcessMessageType::SSE`: For tools specifically designed to work with Server-Sent Events.

For most tools, especially those designed for the primary `streamable_http` provider, you'll return `ProcessMessageType::HTTP`.

**`isStreaming(): bool` (New in v1.3.0)**

This is the new, more intuitive method for controlling communication patterns:

- `return false`: Use standard HTTP request/response (recommended for most tools)
- `return true`: Use Server-Sent Events for real-time streaming

Most tools should return `false` unless you specifically need real-time streaming capabilities like:
- Real-time progress updates for long-running operations
- Live data feeds or monitoring tools
- Interactive tools requiring bidirectional communication

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

**Resource Templates with Dynamic Listing**: Templates can optionally implement a `list()` method to provide concrete resource instances that match the template pattern. This allows clients to discover available resources dynamically. The `list()` method enables ResourceTemplate instances to generate a list of specific resources that can be read through the template's `read()` method.

List available resources using the `resources/list` endpoint and read their
contents with `resources/read`. The `resources/list` endpoint returns an array
of concrete resources, including both static resources and dynamically generated 
resources from templates that implement the `list()` method:

```json
{
  "resources": [
    {
      "uri": "file:///logs/app.log",
      "name": "Application Log",
      "mimeType": "text/plain"
    },
    {
      "uri": "database://users/123",
      "name": "User: John Doe",
      "description": "Profile data for John Doe",
      "mimeType": "application/json"
    }
  ]
}
```

**Dynamic Resource Reading**: Resource templates support URI template patterns (RFC 6570) that allow clients to construct dynamic resource identifiers. When a client requests a resource URI that matches a template pattern, the template's `read()` method is called with extracted parameters to generate the resource content.

Example workflow:
1. Template defines pattern: `"database://users/{userId}/profile"`
2. Client requests: `"database://users/123/profile"`
3. Template extracts `{userId: "123"}` and calls `read()` method
4. Template returns user profile data for user ID 123

You can also list templates separately using the `resources/templates/list` endpoint:

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

### Working with Notifications

Notifications are fire-and-forget messages from MCP clients that always return HTTP 202 Accepted with no response body. They're perfect for logging, progress tracking, event handling, and triggering background processes without blocking the client.

#### Creating Notification Handlers

**Basic command usage:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**Advanced command features:**

```bash
# Interactive mode - prompts for method if not specified
php artisan make:mcp-notification MyHandler

# Automatic method prefix handling
php artisan make:mcp-notification StatusHandler --method=status  # becomes notifications/status

# Class name normalization 
php artisan make:mcp-notification "user activity"  # becomes UserActivityHandler
```

The command provides:
- **Interactive method prompting** when `--method` is not specified
- **Automatic registration guidance** with copy-paste ready code
- **Built-in testing examples** with curl commands 
- **Comprehensive usage instructions** and common use cases

#### Notification Handler Architecture

Each notification handler must implement the `NotificationHandler` abstract class:

```php
abstract class NotificationHandler
{
    // Required: Message type (usually ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // Required: The notification method to handle  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // Required: Execute the notification logic
    abstract public function execute(?array $params = null): void;
}
```

**Key architectural components:**

- **`MESSAGE_TYPE`**: Usually `ProcessMessageType::HTTP` for standard notifications
- **`HANDLE_METHOD`**: The JSON-RPC method this handler processes (must start with `notifications/`)
- **`execute()`**: Contains your notification logic - returns void (no response sent)
- **Constructor validation**: Automatically validates required constants are defined

#### Built-in Notification Handlers

The package includes four pre-built handlers for common MCP scenarios:

**1. InitializedHandler (`notifications/initialized`)**
- **Purpose**: Processes client initialization acknowledgments after successful handshake
- **Parameters**: Client information and capabilities
- **Usage**: Session tracking, client logging, initialization events

**2. ProgressHandler (`notifications/progress`)**
- **Purpose**: Handles progress updates for long-running operations
- **Parameters**: 
  - `progressToken` (string): Unique identifier for the operation
  - `progress` (number): Current progress value
  - `total` (number, optional): Total progress value for percentage calculation
- **Usage**: Real-time progress tracking, upload monitoring, task completion

**3. CancelledHandler (`notifications/cancelled`)**
- **Purpose**: Processes request cancellation notifications
- **Parameters**:
  - `requestId` (string): ID of the request to cancel
  - `reason` (string, optional): Cancellation reason
- **Usage**: Background job termination, resource cleanup, operation abortion

**4. MessageHandler (`notifications/message`)**
- **Purpose**: Handles general logging and communication messages
- **Parameters**:
  - `level` (string): Log level (info, warning, error, debug)
  - `message` (string): The message content
  - `logger` (string, optional): Logger name
- **Usage**: Client-side logging, debugging, general communication

#### Example Handlers for Common Scenarios

```php
// File upload progress tracking
class UploadProgressHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/upload_progress';

    public function execute(?array $params = null): void
    {
        $token = $params['progressToken'] ?? null;
        $progress = $params['progress'] ?? 0;
        $total = $params['total'] ?? 100;
        
        if ($token) {
            Cache::put("upload_progress_{$token}", [
                'progress' => $progress,
                'total' => $total,
                'percentage' => $total ? round(($progress / $total) * 100, 2) : 0,
                'updated_at' => now()
            ], 3600);
            
            // Broadcast real-time update
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// User activity and audit logging
class UserActivityHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/user_activity';

    public function execute(?array $params = null): void
    {
        UserActivity::create([
            'user_id' => $params['userId'] ?? null,
            'action' => $params['action'] ?? 'unknown',
            'resource' => $params['resource'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $params['metadata'] ?? [],
            'created_at' => now()
        ]);
        
        // Trigger security alerts for sensitive actions
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// Background task triggering
class TaskTriggerHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/trigger_task';

    public function execute(?array $params = null): void
    {
        $taskType = $params['taskType'] ?? null;
        $taskData = $params['data'] ?? [];
        
        match ($taskType) {
            'send_email' => SendEmailJob::dispatch($taskData),
            'generate_report' => GenerateReportJob::dispatch($taskData),
            'sync_data' => DataSyncJob::dispatch($taskData),
            'cleanup' => CleanupJob::dispatch($taskData),
            default => Log::warning("Unknown task type: {$taskType}")
        };
    }
}
```

#### Registering Notification Handlers

**In your service provider:**

```php
// In AppServiceProvider or dedicated MCP service provider
public function boot()
{
    $server = app(MCPServer::class);
    
    // Register built-in handlers (optional - they're registered by default)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // Register custom handlers
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### Testing Notifications

**Using curl to test notification handlers:**

```bash
# Test progress notification
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "notifications/progress",
    "params": {
      "progressToken": "upload_123",
      "progress": 75,
      "total": 100
    }
  }'
# Expected: HTTP 202 with empty body

# Test user activity notification  
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", 
    "method": "notifications/user_activity",
    "params": {
      "userId": 123,
      "action": "file_download",
      "resource": "document.pdf"
    }
  }'
# Expected: HTTP 202 with empty body

# Test cancellation notification
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "notifications/cancelled", 
    "params": {
      "requestId": "req_abc123",
      "reason": "User requested cancellation"
    }
  }'
# Expected: HTTP 202 with empty body
```

**Key testing notes:**
- Notifications return **HTTP 202** (never 200)
- Response body is **always empty**
- No JSON-RPC response message is sent
- Check server logs to verify notification processing

#### Error Handling and Validation

**Common validation patterns:**

```php
public function execute(?array $params = null): void
{
    // Validate required parameters
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // Don't throw - notifications should be fault-tolerant
    }
    
    // Validate parameter types
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // Safe parameter extraction with defaults
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // Process notification...
}
```

**Error handling best practices:**
- **Log errors** instead of throwing exceptions
- **Use defensive programming** with null checks and defaults
- **Fail gracefully** - don't break the client's workflow
- **Validate inputs** but continue processing when possible
- **Monitor notifications** through logging and metrics

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

## Deprecated Features for v2.0.0

The following features are deprecated and will be removed in v2.0.0. Please update your code accordingly:

### ToolInterface Changes

**Deprecated since v1.3.0:**
- `messageType(): ProcessMessageType` method
- **Replacement:** Use `isStreaming(): bool` instead
- **Migration Guide:** Return `false` for HTTP tools, `true` for streaming tools
- **Automatic Migration:** Run `php artisan mcp:migrate-tools` to update your tools

**Example Migration:**

```php
// Old approach (deprecated)
public function messageType(): ProcessMessageType
{
    return ProcessMessageType::HTTP;
}

// New approach (v1.3.0+)
public function isStreaming(): bool
{
    return false; // Use false for HTTP, true for streaming
}
```

### Removed Features

**Removed in v1.3.0:**
- `ProcessMessageType::PROTOCOL` enum case (consolidated into `ProcessMessageType::HTTP`)

**Planning for v2.0.0:**
- Complete removal of `messageType()` method from `ToolInterface`
- All tools will be required to implement `isStreaming()` method only
- Simplified tool configuration and reduced complexity

## License

This project is distributed under the MIT license.
