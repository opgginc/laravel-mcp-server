<?php

namespace OPGG\LaravelMcpServer\Server;

use OPGG\LaravelMcpServer\Data\ProcessMessageData;
use OPGG\LaravelMcpServer\Data\Requests\InitializeData;
use OPGG\LaravelMcpServer\Data\Resources\InitializeResource;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Protocol\Handlers\NotificationHandler;
use OPGG\LaravelMcpServer\Protocol\Handlers\RequestHandler;
use OPGG\LaravelMcpServer\Protocol\MCPProtocol;
use OPGG\LaravelMcpServer\Server\Notification\InitializedHandler;
use OPGG\LaravelMcpServer\Server\Request\InitializeHandler;
use OPGG\LaravelMcpServer\Server\Request\PingHandler;
use OPGG\LaravelMcpServer\Server\Request\PromptsGetHandler;
use OPGG\LaravelMcpServer\Server\Request\PromptsListHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesListHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesReadHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesSubscribeHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesTemplatesListHandler;
use OPGG\LaravelMcpServer\Server\Request\ResourcesUnsubscribeHandler;
use OPGG\LaravelMcpServer\Server\Request\ToolsCallHandler;
use OPGG\LaravelMcpServer\Server\Request\ToolsListHandler;
use OPGG\LaravelMcpServer\Services\PromptService\PromptRepository;
use OPGG\LaravelMcpServer\Services\ResourceService\ResourceRepository;
use OPGG\LaravelMcpServer\Services\ToolService\ToolRepository;

/**
 * MCPServer
 *
 * Main server class for the Model Context Protocol (MCP) implementation.
 * This class orchestrates the server's lifecycle, including initialization,
 * handling capabilities, and routing incoming requests and notifications
 * through the configured MCPProtocol handler.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/architecture Describes the overall MCP architecture.
 */
final class MCPServer
{
    /**
     * The protocol handler instance responsible for communication logic.
     */
    private MCPProtocol $protocol;

    /**
     * Information about the server, typically including name and version.
     *
     * @var array{name: string, version: string, title?: string, description?: string, websiteUrl?: string, icons?: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>}
     */
    private array $serverInfo;

    /**
     * Optional human-readable instructions included in initialize responses.
     */
    private ?string $instructions;

    /**
     * Protocol version advertised in initialize responses.
     */
    private string $protocolVersion;

    /**
     * The capabilities supported by this server instance.
     */
    private ServerCapabilities $capabilities;

    /**
     * Flag indicating whether the server has been initialized by a client.
     */
    private bool $initialized = false;

    /**
     * Capabilities reported by the client during initialization. Null if not initialized.
     *
     * @var array<string, mixed>|null
     */
    private ?array $clientCapabilities = null;

    /**
     * Creates a new MCPServer instance.
     *
     * Initializes the server with the communication protocol, server information,
     * and capabilities. Registers the mandatory 'initialize' request handler.
     *
     * @param  MCPProtocol  $protocol  The protocol handler instance.
     * @param  array{name: string, version: string, title?: string, description?: string, websiteUrl?: string, icons?: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>}  $serverInfo  Associative array containing the server identity metadata.
     * @param  ServerCapabilities|null  $capabilities  Optional server capabilities configuration. If null, default capabilities are used.
     * @param  string|null  $instructions  Optional user-facing instructions for clients.
     * @param  string  $protocolVersion  Protocol version to advertise during initialize.
     */
    public function __construct(
        MCPProtocol $protocol,
        array $serverInfo,
        ?ServerCapabilities $capabilities = null,
        ?string $instructions = null,
        string $protocolVersion = MCPProtocol::LATEST_PROTOCOL_VERSION,
    ) {
        $this->protocol = $protocol;
        $this->serverInfo = $serverInfo;
        $this->capabilities = $capabilities ?? new ServerCapabilities;
        $this->instructions = $instructions;
        $this->protocolVersion = $protocolVersion;

        // Register the handler for the mandatory 'initialize' method.
        $this->registerRequestHandler(new InitializeHandler($this));

        // Initialize Default Handlers
        $this->registerRequestHandler(new PingHandler);

        // Register notification handlers
        $this->registerNotificationHandler(new InitializedHandler);
    }

    /**
     * Registers a request handler with the protocol layer.
     * Request handlers process incoming method calls from the client.
     *
     * @param  RequestHandler  $handler  The request handler instance to register.
     */
    public function registerRequestHandler(RequestHandler $handler): void
    {
        $this->protocol->registerRequestHandler($handler);
    }

    /**
     * Static factory method to create a new MCPServer instance with simplified parameters.
     *
     * @param  MCPProtocol  $protocol  The protocol handler instance.
     * @param  string  $name  The server name.
     * @param  string  $version  The server version.
     * @param  ServerCapabilities|null  $capabilities  Optional server capabilities configuration.
     * @param  string|null  $title  Optional display title.
     * @param  string|null  $description  Optional server description.
     * @param  string|null  $websiteUrl  Optional server website URL.
     * @param  array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>  $icons  Optional icon metadata.
     * @param  string|null  $instructions  Optional user-facing instructions.
     * @param  string  $protocolVersion  Protocol version to advertise during initialize.
     * @return self A new MCPServer instance.
     */
    public static function create(
        MCPProtocol $protocol,
        string $name,
        string $version,
        ?ServerCapabilities $capabilities = null,
        ?string $title = null,
        ?string $description = null,
        ?string $websiteUrl = null,
        array $icons = [],
        ?string $instructions = null,
        string $protocolVersion = MCPProtocol::LATEST_PROTOCOL_VERSION,
    ): self {
        $serverInfo = [
            'name' => $name,
            'version' => $version,
        ];

        if ($title !== null) {
            $serverInfo['title'] = $title;
        }
        if ($description !== null) {
            $serverInfo['description'] = $description;
        }
        if ($websiteUrl !== null) {
            $serverInfo['websiteUrl'] = $websiteUrl;
        }
        if ($icons !== []) {
            $serverInfo['icons'] = array_values($icons);
        }

        return new self($protocol, $serverInfo, $capabilities, $instructions, $protocolVersion);
    }

    /**
     * Registers the necessary request handlers for MCP Tools functionality.
     * This typically includes handlers for 'tools/list' and 'tools/call'.
     *
     * @param  ToolRepository  $toolRepository  The repository containing available tools.
     * @return self The current MCPServer instance for method chaining.
     */
    public function registerToolRepository(ToolRepository $toolRepository, int $pageSize = 50): self
    {
        $this->registerRequestHandler(new ToolsListHandler($toolRepository, $pageSize));
        $this->registerRequestHandler(new ToolsCallHandler($toolRepository));

        return $this;
    }

    /**
     * Registers request handlers required for MCP Resources.
     */
    public function registerResourceRepository(ResourceRepository $repository, bool $supportsSubscribe = false): self
    {
        $this->registerRequestHandler(new ResourcesListHandler($repository));
        $this->registerRequestHandler(new ResourcesReadHandler($repository));
        $this->registerRequestHandler(new ResourcesTemplatesListHandler($repository));
        if ($supportsSubscribe) {
            $this->registerRequestHandler(new ResourcesSubscribeHandler);
            $this->registerRequestHandler(new ResourcesUnsubscribeHandler);
        }

        return $this;
    }

    /**
     * Registers request handlers for MCP Prompts.
     */
    public function registerPromptRepository(PromptRepository $repository): self
    {
        $this->registerRequestHandler(new PromptsListHandler($repository));
        $this->registerRequestHandler(new PromptsGetHandler($repository));

        return $this;
    }

    /**
     * Initiates the connection process via the protocol handler.
     */
    public function connect(): void
    {
        $this->protocol->connect();
    }

    /**
     * Initiates the disconnection process via the protocol handler.
     */
    public function disconnect(): void
    {
        $this->protocol->disconnect();
    }

    /**
     * Registers a notification handler with the protocol layer.
     * Notification handlers process incoming notifications from the client (requests without an ID).
     *
     * @param  NotificationHandler  $handler  The notification handler instance to register.
     */
    public function registerNotificationHandler(NotificationHandler $handler): void
    {
        $this->protocol->registerNotificationHandler($handler);
    }

    /**
     * Handles the 'initialize' request from the client.
     * Stores client capabilities, checks protocol version, and marks the server as initialized.
     * Throws an error if the server is already initialized.
     *
     * @param  InitializeData  $data  The data object containing initialization parameters from the client.
     * @return InitializeResource A resource object containing the server's initialization response.
     *
     * @throws JsonRpcErrorException If the server has already been initialized (JSON-RPC error code -32600).
     */
    public function initialize(InitializeData $data): InitializeResource
    {
        if ($this->initialized) {
            throw new JsonRpcErrorException(message: 'Server already initialized', code: JsonRpcErrorCode::INVALID_REQUEST);
        }

        $this->initialized = true;

        $this->clientCapabilities = $data->capabilities;
        $initializeResource = new InitializeResource(
            $this->serverInfo,
            $this->capabilities->toArray(),
            $this->instructions,
            $this->protocolVersion,
        );

        return $initializeResource;
    }

    /**
     * Returns client capabilities captured during initialize, if available.
     *
     * @return array<string, mixed>|null
     */
    public function getClientCapabilities(): ?array
    {
        return $this->clientCapabilities;
    }

    /**
     * Forwards a request message to a specific client via the protocol handler.
     * Used for server-initiated requests to the client (if supported by the protocol/transport).
     *
     * @param  string  $clientId  The identifier of the target client.
     * @param  array<string, mixed>  $message  The request message payload (following JSON-RPC structure).
     */
    public function requestMessage(string $clientId, array $message): ProcessMessageData
    {
        return $this->protocol->handleMessage(clientId: $clientId, message: $message);
    }
}
