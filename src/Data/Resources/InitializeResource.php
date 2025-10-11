<?php

namespace OPGG\LaravelMcpServer\Data\Resources;

use OPGG\LaravelMcpServer\Protocol\MCPProtocol;

/**
 * Represents the response data for the MCP Initialize request.
 * This class structures the information sent back to the client upon successful initialization.
 */
class InitializeResource
{
    /**
     * The version of the Model Context Protocol being used.
     * Defaults to the version defined in MCPProtocol.
     */
    public string $protocolVersion = MCPProtocol::PROTOCOL_VERSION;

    /**
     * An array describing the capabilities of the server.
     * This typically includes information about supported features or tools.
     */
    public array $capabilities;

    /**
     * Information about the server itself.
     * Contains the server's name and version.
     *
     * @var array{name: string, version: string}
     */
    public array $serverInfo;

    /**
     * Constructs a new InitializeResource instance.
     *
     * @param  string  $name  The name of the server.
     * @param  string  $version  The version of the server.
     * @param  array  $capabilities  The capabilities supported by the server.
     * @param  string  $protocolVersion  The protocol version being used.
     */
    /**
     * @param  string  $protocolVersion  Defaults to MCP revision 2025-06-18 per the upstream spec.
     *                                   This ensures initialize echoes the negotiated version documented at
     *                                   https://modelcontextprotocol.io/specification/2025-06-18#initialization.
     */
    public function __construct(string $name, string $version, array $capabilities, string $protocolVersion = '2025-06-18')
    {
        $this->serverInfo = [
            'name' => $name,
            'version' => $version,
        ];
        $this->capabilities = $capabilities;
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * Creates an InitializeResource instance from an array.
     * Useful for hydrating the object from serialized data.
     *
     * @param  array  $data  The data array, expected to contain 'name', 'version', and 'capabilities'.
     * @return self A new instance of InitializeResource.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'] ?? 'unknown',
            $data['version'] ?? '1.0',
            $data['capabilities'] ?? []
        );
    }

    /**
     * Converts the InitializeResource instance to an array.
     * Suitable for serialization or sending as part of an API response.
     *
     * @return array An associative array representing the resource.
     */
    public function toArray(): array
    {
        return [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
            'serverInfo' => $this->serverInfo,
        ];
    }
}
