<?php

namespace OPGG\LaravelMcpServer\Server;

use stdClass;

/**
 * Represents the server's capabilities according to the MCP specification.
 * This class defines what features the MCP server supports, such as tools.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/architecture
 */
final class ServerCapabilities
{
    /**
     * Indicates whether the server supports the MCP tools feature.
     * If true, the server can register and expose tools to the client.
     *
     * @see https://modelcontextprotocol.io/docs/concepts/tools
     */
    private bool $supportsTools = false;

    /**
     * Optional configuration specific to the tools capability.
     * This structure can be defined by the specific server implementation
     * to provide further details about the supported tools, if needed.
     * If null and tools are supported, it might default to an empty object during serialization.
     */
    private ?array $toolsConfig = null;

    /**
     * Indicates whether the server supports the MCP resources feature.
     */
    private bool $supportsResources = false;

    /**
     * Optional configuration for the resources capability.
     */
    private ?array $resourcesConfig = null;

    /**
     * Indicates whether the server supports the MCP prompts feature.
     */
    private bool $supportsPrompts = false;

    /**
     * Optional configuration for prompts.
     */
    private ?array $promptsConfig = null;

    /**
     * Enables the tools capability for the server instance.
     * Allows specifying optional configuration details for the tools feature.
     *
     * @param  array|null  $config  Optional configuration data specific to the tools capability.
     *                              Defaults to an empty array if not provided.
     * @return self Returns the instance for method chaining.
     *
     * @see https://modelcontextprotocol.io/docs/concepts/tools
     */
    public function withTools(?array $config = []): self
    {
        $this->supportsTools = true;
        $config = $config ?? [];

        // The MCP 2025-06-18 specification mandates advertising whether `tools/list_changed`
        // notifications are emitted via the `listChanged` flag within the tools capability block.
        // When a caller does not provide an explicit value we default to `false` so the payload
        // still satisfies the schema described at
        // https://modelcontextprotocol.io/specification/2025-06-18#capabilities.
        if (! array_key_exists('listChanged', $config)) {
            $config['listChanged'] = false;
        }

        $this->toolsConfig = $config;

        return $this;
    }

    /**
     * Enables the resources capability for the server.
     */
    public function withResources(?array $config = []): self
    {
        $this->supportsResources = true;
        $this->resourcesConfig = $config;

        return $this;
    }

    /**
     * Enables the prompts capability for the server.
     */
    public function withPrompts(?array $config = []): self
    {
        $this->supportsPrompts = true;
        $this->promptsConfig = $config;

        return $this;
    }

    /**
     * Converts the server capabilities configuration into an array format suitable for JSON serialization.
     * Only includes capabilities that are actively enabled.
     *
     * @return array<string, mixed> An associative array representing the enabled server capabilities.
     *                              For tools, if enabled but no config is set, it defaults to an empty JSON object.
     */
    public function toArray(): array
    {
        $capabilities = [];

        if ($this->supportsTools) {
            // Use an empty stdClass to ensure JSON serialization as {} instead of [] for empty arrays.
            $capabilities['tools'] = $this->toolsConfig ?? new stdClass;
        }

        if ($this->supportsResources) {
            $capabilities['resources'] = $this->resourcesConfig ?? new stdClass;
        }

        if ($this->supportsPrompts) {
            $capabilities['prompts'] = $this->promptsConfig ?? new stdClass;
        }

        return $capabilities;
    }
}
