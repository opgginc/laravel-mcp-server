<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Activation
    |--------------------------------------------------------------------------
    |
    | Enable or disable the MCP server functionality. When disabled, no routes
    | will be registered and the server will not respond to any requests.
    | This is useful for staging environments or feature flags.
    |
    */
    'enabled' => true, // Set to false if you want to disable the MCP server

    /*
    |--------------------------------------------------------------------------
    | Server Information
    |--------------------------------------------------------------------------
    |
    | Configuration for the MCPServer instance. These values are used when
    | registering the MCPServer as a singleton in the service container.
    | The name and version are sent to clients during the initialize handshake,
    | so that the LLM will understand what's this server about.
    |
    */
    'server' => [
        'name' => 'OP.GG MCP Server',
        'version' => '0.1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport Provider Configuration
    |--------------------------------------------------------------------------
    |
    | The transport provider determines how the MCP server communicates with clients.
    |
    | Available providers:
    | - 'streamable_http' (recommended): Standard HTTP requests, works everywhere
    | - 'sse' (legacy, deprecated): Server-Sent Events with pub/sub, requires specific setup
    |
    | Note: SSE provider requires Laravel Octane or similar for concurrent connections
    |
    */
    'server_provider' => 'streamable_http',

    /*
    |--------------------------------------------------------------------------
    | MCP Server Endpoints
    |--------------------------------------------------------------------------
    |
    | Configure the base path for MCP endpoints:
    | - Streamable HTTP: GET/POST /{default_path}
    | - SSE (legacy): GET /{default_path}/sse, POST /{default_path}/message
    |
    */
    'default_path' => 'mcp',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to MCP routes. Use this to protect your endpoints
    | with authentication, rate limiting, or other middleware as needed.
    |
    | Example middlewares:
    | - 'auth:api' for API authentication
    | - 'throttle:60,1' for rate limiting
    | - 'cors' for CORS handling
    |
    */
    'middlewares' => [
        // 'auth:api',
        // 'throttle:60,1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain Restriction
    |--------------------------------------------------------------------------
    |
    | Restrict MCP server routes to specific domain(s). This is useful when:
    | - Running multiple applications on different subdomains
    | - Separating API endpoints from main application
    | - Implementing multi-tenant architectures
    |
    | Options:
    | - null: Allow access from all domains (default)
    | - string: Restrict to a single domain
    | - array: Restrict to multiple domains
    |
    | Examples:
    | 'domain' => null,                                    // No restriction
    | 'domain' => 'api.example.com',                      // Single domain
    | 'domain' => ['api.example.com', 'admin.example.com'], // Multiple domains
    |
    | Note: When using multiple domains, separate routes will be registered
    | for each domain to ensure proper routing.
    |
    */
    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | SSE Adapter Configuration (Legacy Provider Only)
    |--------------------------------------------------------------------------
    |
    | Configuration for SSE adapters used by the legacy 'sse' provider.
    | Only applies when server_provider is set to 'sse'.
    |
    | Adapters function as pub/sub message brokers between clients and server.
    | The Redis adapter uses Redis lists as message queues with unique client IDs.
    |
    */
    'sse_adapter' => 'redis',
    'adapters' => [
        'redis' => [
            'prefix' => 'mcp_sse_',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Tools Registry
    |--------------------------------------------------------------------------
    |
    | Register your MCP tools here. Tools must implement ToolInterface.
    | Tools are automatically discovered and made available to MCP clients.
    |
    | Example:
    | App\MCP\Tools\DatabaseQueryTool::class,
    | App\MCP\Tools\FileOperationTool::class,
    |
    | @see https://modelcontextprotocol.io/docs/concepts/tools
    |
    */
    'tools' => [
        // Example tools (remove in production)
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool::class,
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool::class,

        // Register your custom tools here
        // App\MCP\Tools\YourCustomTool::class,
    ],
];
