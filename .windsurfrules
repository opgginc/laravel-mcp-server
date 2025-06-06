# Laravel MCP Server Development Rules

## Project Overview
This is a Laravel package for implementing Model Context Protocol (MCP) servers with Streamable HTTP transport and legacy SSE support. Focus on secure, enterprise-ready MCP implementations.

## Technical Specification
- Support Laravel 10, 11, 12
- Support PHP 8.2+
- Namespace: `OPGG\LaravelMcpServer\`

## Common Commands

### Testing and Quality
- Run tests: `vendor/bin/pest`
- Code formatting: `vendor/bin/pint`
- Static analysis: `vendor/bin/phpstan analyse`

### MCP Tool Development
- Create tool: `php artisan make:mcp-tool ToolName`
- Test tool: `php artisan mcp:test-tool ToolName`
- List tools: `php artisan mcp:test-tool --list`
- Test with JSON: `php artisan mcp:test-tool ToolName --input='{"param":"value"}'`

### Configuration
- Publish config: `php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"`

### Development Server
**CRITICAL**: Never use `php artisan serve` with SSE provider - use Laravel Octane:
```bash
composer require laravel/octane
php artisan octane:install --server=frankenphp
php artisan octane:start
```

## Laravel Package Development Guidelines

### Code Style
- Add `use` statements for all Facade classes to call them more concisely
- Use Facade classes or original Laravel classes instead of helper functions
- Create new class and refactor if file exceeds 300 lines
- Use `env()` instead of `Config` facade in config files (`/config/mcp-server.php`)

### Type Annotations
- When specifying nullable types, use `string|null` instead of `?string`
- Always place `null` at the end of union types
- Add type hints and return types to all methods for better IDE support

### MCP Implementation Standards
- All tools must implement `ToolInterface`
- Register tools in `config/mcp-server.php`
- Use JSON-RPC 2.0 message format strictly
- Support both Streamable HTTP and SSE transports
- Implement proper error handling with `JsonRpcErrorException`

## Architecture Overview

### Core Components
- **MCPServer** (`src/Server/MCPServer.php`): Main orchestrator for server lifecycle
- **MCPProtocol** (`src/Protocol/MCPProtocol.php`): JSON-RPC 2.0 message processing
- **Transport Layer**: Abstracted communication (Streamable HTTP/SSE)
- **Handler Pattern**: RequestHandler/NotificationHandler for processing
- **Repository Pattern**: ToolRepository for tool management

### Key Handlers
- **InitializeHandler**: Client-server handshake and capability negotiation
- **ToolsListHandler**: Returns available MCP tools to clients
- **ToolsCallHandler**: Executes specific tool calls with parameters
- **PingHandler**: Health check endpoint

### Configuration
- Primary config: `config/mcp-server.php`
- Environment variables: `MCP_SERVER_ENABLED`
- Default transport: `streamable_http` (recommended)
- Legacy SSE with Redis pub/sub adapter

### Endpoints
- **Streamable HTTP**: `GET/POST /{default_path}` (default: `/mcp`)
- **SSE (legacy)**: `GET /{default_path}/sse`, `POST /{default_path}/message`

## File Organization

### Tool Development
- Create in `app/MCP/Tools/` (via make command)
- Use `src/stubs/tool.stub` template
- Examples in `src/Services/ToolService/Examples/`
- Interface: `src/Services/ToolService/ToolInterface.php`

### Transport Layer
- `src/Transports/StreamableHttpTransport.php`: HTTP transport
- `src/Transports/SseTransport.php`: SSE transport
- `src/Transports/SseAdapters/RedisAdapter.php`: Redis pub/sub

## Development Guidelines

### When Creating Tools
1. Use `php artisan make:mcp-tool ToolName`
2. Implement all ToolInterface methods
3. Define proper input schema validation
4. Test with `php artisan mcp:test-tool ToolName`
5. Register in config before production

### Error Handling
- Use `JsonRpcErrorException` for MCP errors
- Implement proper error codes from `JsonRpcErrorCode` enum
- Provide meaningful error messages
- Log errors appropriately

## Documentation Standards

### PHPDoc Requirements
- Document all public methods and classes with PHPDoc annotations
- Include `@param`, `@return`, and `@throws` tags for all methods
- Document configuration options with sample values and explanations
- Use descriptive variable and method names for self-documentation
- Add inline comments for complex logic explaining the "why" not just the "what"

### Documentation Maintenance
- Keep documentation up-to-date when changing functionality
- Document breaking changes prominently in CHANGELOG.md and README.md
- Include version compatibility information in all documentation
- Document expected environment variables and their purposes
- Document how the package integrates with Laravel's existing features

## MCP Protocol References
- https://modelcontextprotocol.io/docs/concepts/architecture
- https://modelcontextprotocol.io/docs/concepts/tools
- https://modelcontextprotocol.io/docs/concepts/transports

## Don't Do
- Don't use `php artisan serve` with SSE provider
- Don't hardcode configuration values
- Don't skip input validation in tools
- Don't commit sensitive data (API keys, secrets)
- Don't break JSON-RPC 2.0 message format
- Don't modify core MCP protocol handlers without careful consideration
- Don't use `?string` syntax - use `string|null` instead
