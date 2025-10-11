<?php

/**
 * Laravel MCP Server Configuration
 *
 * This configuration file controls all aspects of your Model Context Protocol (MCP) server.
 * The MCP server enables Large Language Models (LLMs) to interact with your Laravel
 * application through standardized tools, resources, and prompts.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Activation
    |--------------------------------------------------------------------------
    |
    | Enable or disable the MCP server functionality. When disabled, no routes
    | will be registered and the server will not respond to any requests.
    | This is useful for disabling the MCP server if you want to pause or stop
    | the server temporarily.
    |
    */
    'enabled' => true,

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
    | NAMING BEST PRACTICES:
    | - Use descriptive names that indicate purpose (e.g., "E-commerce API", "CRM Server")
    | - Include your organization/app name for identification
    | - Avoid generic names like "MCP Server" in production
    |
    | VERSION GUIDELINES:
    | - Use semantic versioning (MAJOR.MINOR.PATCH)
    | - Increment when adding/changing tools or capabilities
    | - Clients may use version for compatibility checks
    |
    | EXAMPLES:
    | 'name' => 'Acme E-commerce MCP Server',
    | 'name' => 'Customer Support AI Assistant',
    | 'name' => 'Data Analytics MCP Gateway',
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
    | AVAILABLE PROVIDERS:
    | ===================
    |
    | 'streamable_http' (RECOMMENDED):
    | - Uses standard HTTP requests with JSON-RPC protocol
    | - Works on all hosting platforms (shared hosting, serverless, containers)
    | - No special server requirements
    | - Better for enterprise environments and security
    | - Simpler to debug and monitor
    |
    | 'sse' (LEGACY, DEPRECATED):
    | - Uses Server-Sent Events with pub/sub messaging
    | - Requires Laravel Octane or similar for concurrent connections
    | - May not work on platforms with short HTTP timeouts
    | - More complex infrastructure requirements
    | - Being phased out in favor of streamable_http
    |
    | PERFORMANCE COMPARISON:
    | - streamable_http: Lower latency, better resource usage
    | - sse: Higher latency due to pub/sub overhead
    |
    | HOSTING COMPATIBILITY:
    | - Shared hosting: streamable_http only
    | - VPS/Dedicated: Both (streamable_http recommended)
    | - Serverless (AWS Lambda, Vercel): streamable_http only
    | - Kubernetes/Docker: Both (streamable_http recommended)
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
    | SECURITY MIDDLEWARE (HIGHLY RECOMMENDED FOR PRODUCTION):
    | ========================================================
    |
    | Authentication:
    | - 'auth:api' - Laravel Sanctum/Passport API authentication
    | - 'auth:sanctum' - Sanctum token authentication
    | - Custom auth middleware for specific requirements
    |
    | Rate Limiting:
    | - 'throttle:60,1' - 60 requests per minute per IP
    | - 'throttle:100,1' - 100 requests per minute per IP
    | - 'throttle:api' - Use the 'api' rate limiter from RouteServiceProvider
    |
    | CORS (Cross-Origin Resource Sharing):
    | - 'cors' - Enable CORS for web clients
    | - Required if accessing from browser-based applications
    |
    | Custom Security:
    | - IP whitelisting middleware
    | - Request signature validation
    | - Audit logging middleware
    |
    | EXAMPLE CONFIGURATIONS:
    | ======================
    |
    | Development (minimal security):
    | 'middlewares' => ['throttle:60,1'],
    |
    | Production (maximum security):
    | 'middlewares' => [
    |     'auth:sanctum',     // Require authentication
    |     'throttle:100,1',   // Rate limiting
    |     'cors',            // CORS support
    |     'audit.log',       // Custom audit logging
    | ],
    |
    | API-only (no web interface):
    | 'middlewares' => [
    |     'auth:api',
    |     'throttle:api',
    | ],
    |
    | ENVIRONMENT-SPECIFIC MIDDLEWARE:
    | You can use environment variables to conditionally apply middleware:
    | 'middlewares' => array_filter([
    |     env('APP_ENV') !== 'local' ? 'auth:api' : null,
    |     'throttle:' . env('MCP_RATE_LIMIT', '60') . ',1',
    | ]),
    |
    */
    'middlewares' => [
        // Development: Minimal middleware for easier testing
        // 'throttle:60,1',

        // Production: Uncomment and customize these for security
        // 'auth:sanctum',     // Require authentication
        // 'throttle:100,1',   // Rate limiting
        // 'cors',             // CORS support if needed

        // Custom middleware examples:
        // 'mcp.audit',        // Log all MCP requests
        // 'mcp.whitelist',    // IP whitelisting
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
    | ORGANIZATION TIPS:
    | ==================
    | - Group related tools by feature/domain
    | - Use consistent naming conventions
    | - Document tool dependencies and requirements
    | - Consider tool permissions and access levels
    |
    | DEVELOPMENT WORKFLOW:
    | ====================
    | 1. Create tool: php artisan make:mcp-tool YourToolName
    | 2. Implement logic in the generated class
    | 3. Register the tool in this array
    | 4. Test: php artisan mcp:test-tool YourToolName
    |
    | @see https://modelcontextprotocol.io/docs/concepts/tools
    |
    */
    'tools' => [
        // Example tools - Remove these in production and add your own
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool::class,
        \OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool::class,

        // ===== REGISTER YOUR CUSTOM TOOLS BELOW =====
        // App\MCP\Tools\Database\Your::class,
        // App\MCP\Tools\Database\SearchProductsTool::class,
        // App\MCP\Tools\Api\FetchWeatherTool::class,

        // === Business Logic Tools ===
        // App\MCP\Tools\Ecommerce\CalculateShippingTool::class,
        // App\MCP\Tools\Crm\CreateLeadTool::class,
        // App\MCP\Tools\Analytics\GenerateReportTool::class,

        // === Utility Tools ===
        // App\MCP\Tools\Text\FormatContentTool::class,
        // App\MCP\Tools\Date\CalculateDurationTool::class,
        // App\MCP\Tools\Math\StatisticsCalculatorTool::class,

        // === Integration Tools ===
        // App\MCP\Tools\Email\SendNotificationTool::class,
        // App\MCP\Tools\Sms\SendMessageTool::class,
        // App\MCP\Tools\External\PaymentProcessorTool::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools Capability Flags
    |--------------------------------------------------------------------------
    |
    | The MCP 2025-06-18 revision requires servers to declare whether they emit
    | `notifications/tools/list_changed` via the `listChanged` capability flag.
    | You can opt-in through the `MCP_TOOLS_LIST_CHANGED` environment variable.
    | @see https://modelcontextprotocol.io/specification/2025-06-18#capabilities
    |
    */
    'tool_capabilities' => [
        'list_changed' => env('MCP_TOOLS_LIST_CHANGED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tools List Pagination
    |--------------------------------------------------------------------------
    |
    | `tools/list` now supports cursor-based pagination per the 2025-06-18 spec.
    | Configure the maximum number of tool definitions returned per page here.
    | Clients can request subsequent pages by passing the `nextCursor` value as
    | the `cursor` parameter. @see
    | https://modelcontextprotocol.io/specification/2025-06-18#listing-tools
    |
    */
    'tools_list' => [
        'page_size' => env('MCP_TOOLS_PAGE_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Resources Registry
    |--------------------------------------------------------------------------
    |
    | Resources expose data from your server that can be read by MCP clients.
    | Unlike tools (which perform actions), resources provide access to existing
    | data sources like files, databases, APIs, or real-time data streams.
    |
    | RESOURCE TYPES & EXAMPLES:
    | =========================
    |
    | Static Resources (fixed URIs):
    | - Log files: application logs, error logs, access logs
    | - Configuration files: settings, environment data
    | - Documentation: API docs, user guides, help text
    | - Reports: generated reports, analytics data
    |
    | Dynamic Resources (via templates):
    | - User profiles: user/{id}/profile
    | - Product data: products/{sku}/details
    | - Time-series data: metrics/{date}/hourly
    | - Database records: tables/{table}/records
    |
    | RESOURCE VS TOOLS:
    | ==================
    | Resources: Read-only data access ("GET a user's profile")
    | Tools: Actions and computations ("CREATE a new user")
    |
    | DEVELOPMENT WORKFLOW:
    | ====================
    | 1. Static: php artisan make:mcp-resource LogAnalysisResource
    | 2. Dynamic: php artisan make:mcp-resource-template UserDataTemplate
    | 3. Implement the read() method in your resource class
    | 4. Register in the appropriate array below
    | 5. Test with: curl -X POST /mcp -d '{"method":"resources/list"}'
    |
    | @see https://modelcontextprotocol.io/docs/concepts/resources
    |
    */

    // Static Resources - Fixed data sources with known URIs
    'resources' => [
        // Example resource - Remove in production
        \OPGG\LaravelMcpServer\Services\ResourceService\Examples\LogFileResource::class,
        \OPGG\LaravelMcpServer\Services\ResourceService\Examples\UserListResource::class,

        // ===== REGISTER YOUR STATIC RESOURCES BELOW =====
        // Examples:
        // App\MCP\Resources\ApplicationLogsResource::class,
        // App\MCP\Resources\SystemStatusResource::class,
        // App\MCP\Resources\ApiDocumentationResource::class,
        // App\MCP\Resources\ConfigurationResource::class,
    ],

    // Resource Templates - Dynamic resources using URI patterns
    'resource_templates' => [
        // Example template - Remove in production
        \OPGG\LaravelMcpServer\Services\ResourceService\Examples\LogFileTemplate::class,
        \OPGG\LaravelMcpServer\Services\ResourceService\Examples\UserResourceTemplate::class,

        // ===== REGISTER YOUR RESOURCE TEMPLATES BELOW =====
        // Examples:
        // App\MCP\ResourceTemplates\UserProfileTemplate::class,     // users/{id}/profile
        // App\MCP\ResourceTemplates\ProductDataTemplate::class,     // products/{sku}/data
        // App\MCP\ResourceTemplates\OrderHistoryTemplate::class,    // orders/{userId}/history
        // App\MCP\ResourceTemplates\MetricsTemplate::class,         // metrics/{date}/{type}
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Prompts Registry
    |--------------------------------------------------------------------------
    |
    | Prompts are reusable templates that help LLMs understand how to use your
    | tools and resources effectively. They provide structured guidance and
    | can be parameterized for different scenarios.
    |
    | PROMPT CATEGORIES & EXAMPLES:
    | =============================
    |
    | System Prompts:
    | - Welcome messages and introductions
    | - Feature explanations and capabilities
    | - Usage guidelines and best practices
    |
    | Task-Specific Prompts:
    | - Data analysis workflows
    | - Report generation templates
    | - Troubleshooting guides
    |
    | User Interaction Prompts:
    | - Customer service responses
    | - Product recommendations
    | - Help and support templates
    |
    | PROMPT DESIGN PRINCIPLES:
    | ========================
    | - Clear and specific instructions
    | - Include examples and context
    | - Use parameterization for flexibility
    | - Provide fallback options
    | - Test with real scenarios
    |
    | PROMPT ARGUMENTS:
    | ================
    | Prompts can accept arguments to customize their content:
    | - User information (name, role, preferences)
    | - Context data (current task, environment)
    | - Options and configurations
    | - Dynamic content insertion
    |
    | DEVELOPMENT WORKFLOW:
    | ====================
    | 1. Create: php artisan make:mcp-prompt YourPromptName
    | 2. Define arguments and template text
    | 3. Register in the array below
    | 4. Test: curl -X POST /mcp -d '{"method":"prompts/get","params":{"name":"your-prompt"}}'
    |
    | EXAMPLES OF EFFECTIVE PROMPTS:
    | ==============================
    |
    | Welcome Prompt:
    | "Welcome {username}! I can help you with {available_features}.
    |  To get started, try asking me to {example_task}."
    |
    | Analysis Prompt:
    | "Analyze the {data_type} data from {date_range}.
    |  Focus on {analysis_focus} and provide insights about {key_metrics}.
    |  Format the results as {output_format}."
    |
    | Troubleshooting Prompt:
    | "Help troubleshoot {issue_type} in {system_component}.
    |  Check {diagnostic_steps} and report findings in {report_format}."
    |
    | @see https://modelcontextprotocol.io/docs/concepts/prompts
    |
    */
    'prompts' => [
        // Example prompt - Remove in production
        \OPGG\LaravelMcpServer\Services\PromptService\Examples\WelcomePrompt::class,

        // ===== REGISTER YOUR CUSTOM PROMPTS BELOW =====
        // Examples:

        // === System & Welcome Prompts ===
        // App\MCP\Prompts\WelcomeNewUserPrompt::class,
        // App\MCP\Prompts\FeatureIntroductionPrompt::class,
        // App\MCP\Prompts\SystemCapabilitiesPrompt::class,

        // === Task-Specific Prompts ===
        // App\MCP\Prompts\DataAnalysisPrompt::class,
        // App\MCP\Prompts\ReportGenerationPrompt::class,
        // App\MCP\Prompts\TroubleshootingGuidePrompt::class,

        // === User Interaction Prompts ===
        // App\MCP\Prompts\CustomerSupportPrompt::class,
        // App\MCP\Prompts\ProductRecommendationPrompt::class,
        // App\MCP\Prompts\HelpDocumentationPrompt::class,

        // === Domain-Specific Prompts ===
        // App\MCP\Prompts\EcommerceAssistantPrompt::class,
        // App\MCP\Prompts\CrmWorkflowPrompt::class,
        // App\MCP\Prompts\AnalyticsDashboardPrompt::class,
    ],
];
