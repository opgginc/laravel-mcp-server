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
     * Mirrors MCP Implementation shape.
     *
     * @var array{name: string, version: string, title?: string, description?: string, websiteUrl?: string, icons?: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>}
     */
    public array $serverInfo;

    /**
     * Human-readable guidance shown to users by clients.
     */
    public ?string $instructions = null;

    /**
     * Constructs a new InitializeResource instance.
     *
     * @param  array{name: string, version: string, title?: string, description?: string, websiteUrl?: string, icons?: array<int, array{src: string, mimeType?: string, sizes?: array<int, string>, theme?: 'light'|'dark'}>}  $serverInfo
     * @param  array  $capabilities  The capabilities supported by the server.
     * @param  string|null  $instructions  Optional human-readable instructions for clients.
     * @param  string  $protocolVersion  The protocol version being used.
     */
    public function __construct(array $serverInfo, array $capabilities, ?string $instructions = null, string $protocolVersion = MCPProtocol::PROTOCOL_VERSION)
    {
        $this->serverInfo = $serverInfo;
        $this->capabilities = $capabilities;
        $this->instructions = $instructions;
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
        $serverInfo = $data['serverInfo'] ?? [
            'name' => $data['name'] ?? 'unknown',
            'version' => $data['version'] ?? '1.0',
        ];

        if (! is_array($serverInfo)) {
            $serverInfo = [
                'name' => 'unknown',
                'version' => '1.0',
            ];
        }

        return new self(
            $serverInfo,
            is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [],
            is_string($data['instructions'] ?? null) ? $data['instructions'] : null,
            is_string($data['protocolVersion'] ?? null) ? $data['protocolVersion'] : MCPProtocol::PROTOCOL_VERSION,
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
        $payload = [
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities,
            'serverInfo' => $this->serverInfo,
        ];

        if ($this->instructions !== null) {
            $payload['instructions'] = $this->instructions;
        }

        return $payload;
    }
}
