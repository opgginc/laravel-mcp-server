## Common Commands

### Testing and Quality Assurance
- **Run tests**: `vendor/bin/pest`
- **Run tests with coverage**: `vendor/bin/pest --coverage`
- **Code formatting**: `vendor/bin/pint`
- **Static analysis**: `vendor/bin/phpstan analyse`

### MCP Tool Development
- **Create new MCP tool**: `php artisan make:mcp-tool ToolName`
- **Test specific tool**: `php artisan mcp:test-tool ToolName`
- **List all tools**: `php artisan mcp:test-tool --list`
- **Test tool with JSON input**: `php artisan mcp:test-tool ToolName --input='{"param":"value"}'`

### Configuration Publishing
- **Publish config file**: `php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"`

### Development Server (IMPORTANT)
**WARNING**: `php artisan serve` CANNOT be used with this package when you use SSE driver.

**Use Laravel Octane instead**:
```bash
composer require laravel/octane
php artisan octane:install --server=frankenphp
php artisan octane:start
```

## Architecture Overview

### Core Components

**MCPServer (`src/Server/MCPServer.php`)**: Main orchestrator that manages the MCP server lifecycle, initialization, and request routing. Handles client capabilities negotiation and registers request/notification handlers.

**MCPProtocol (`src/Protocol/MCPProtocol.php`)**: Protocol implementation that handles JSON-RPC 2.0 message processing. Routes requests to appropriate handlers and manages communication with transport layer.

**Transport Layer**: Abstracted transport system supporting multiple providers:
- **Streamable HTTP** (recommended): Standard HTTP requests, works on all platforms
- **SSE (legacy)**: Server-Sent Events with pub/sub architecture using Redis adapter

### Request Handling Flow

1. Transport receives JSON-RPC 2.0 messages
2. MCPProtocol validates and routes messages
3. Registered handlers (RequestHandler/NotificationHandler) process requests
4. Results are sent back through the transport layer

### Key Handlers
- **InitializeHandler**: Handles client-server handshake and capability negotiation
- **ToolsListHandler**: Returns available MCP tools to clients
- **ToolsCallHandler**: Executes specific tool calls with parameters
- **PingHandler**: Health check endpoint

### Tool System
Tools implement `ToolInterface` and are registered in `config/mcp-server.php`. Each tool defines:
- Input schema for parameter validation
- Execution logic
- Output formatting

### Configuration
Primary config: `config/mcp-server.php`
- Server info (name, version)
- Transport provider selection
- Tool registration
- SSE adapter settings (Redis connection, TTL)
- Route middlewares

### Environment Variables
- `MCP_SERVER_ENABLED`: Enable/disable server

### Endpoints
- **Streamable HTTP**: `GET/POST /{default_path}` (default: `/mcp`)
- **SSE (legacy)**: `GET /{default_path}/sse`, `POST /{default_path}/message`

### Key Files for Tool Development
- Tool interface: `src/Services/ToolService/ToolInterface.php`
- Tool repository: `src/Services/ToolService/ToolRepository.php`
- Example tools: `src/Services/ToolService/Examples/`
- Tool stub template: `src/stubs/tool.stub`

## Package Development Notes

### Project Structure
This is a Laravel package distributed via Composer. Key structural elements:
- **Source code**: All functionality in `src/` directory
- **Configuration**: Published config file at `config/mcp-server.php`
- **Service Provider**: Auto-registered via Laravel package discovery
- **Testing**: Uses Pest testing framework with Orchestra Testbench
- **Quality Tools**: PHPStan (level 5), Laravel Pint for formatting

### Breaking Changes & Migration
v1.1.0 introduced breaking changes to `ToolInterface`:
- Method renames (e.g., `getName()` → `name()`)
- New required `messageType()` method
- Use `php artisan mcp:migrate-tools` for automated migration

### Environment Requirements
- PHP >=8.2
- Laravel >=10.x
- Redis (for SSE legacy transport only)
- Laravel Octane with FrankenPHP (recommended for SSE, required for development server)

## Final Test Guide
To verify the complete MCP workflow, use the following script:

1. Run `./scripts/test-setup.sh` from the project root
2. Navigate to the created directory (`laravel-mcp-test`) and run `./run-test.sh`
   - The server will start and execute example tools.
   - You need to wait more than 30 seconds, then it will setup properly so that you can test the MCP server.
