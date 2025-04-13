<?php

namespace OPGG\LaravelMcpServer\Data\Requests;

/**
 * JSON-RPC Request Notification Data
 * Represents the data structure for a JSON-RPC notification according to the MCP specification.
 * @see https://modelcontextprotocol.io/specification/2024-11-05/basic/index#notifications
 */
class NotificationData
{
    /**
     * The method to be invoked.
     * @var string
     */
    public string $method;

    /**
     * The JSON-RPC version string. MUST be "2.0".
     * @var string
     */
    public string $jsonRpc;

    /**
     * The parameters for the notification. Can be structured by position or by name.
     * @var array<mixed>|null
     */
    public ?array $params;

    /**
     * Constructor for NotificationData.
     *
     * @param string $method The notification method name.
     * @param string $jsonRpc The JSON-RPC version (should be "2.0").
     * @param array<mixed>|null $params The notification parameters.
     */
    public function __construct(string $method, string $jsonRpc, ?array $params)
    {
        $this->method = $method;
        $this->jsonRpc = $jsonRpc;
        $this->params = $params;
    }

    /**
     * Creates a NotificationData object from an array.
     *
     * @param array<string, mixed> $data The source data array, typically from a decoded JSON request.
     * @return self Returns an instance of NotificationData.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            method: $data['method'],
            jsonRpc: $data['jsonrpc'],
            params: $data['params'] ?? null
        );
    }

    /**
     * Converts the NotificationData object back into an array format suitable for JSON encoding.
     *
     * @return array<string, mixed> Returns an array representation of the notification.
     */
    public function toArray(): array
    {
        $result = [
            'jsonrpc' => $this->jsonRpc,
            'method' => $this->method,
        ];

        if ($this->params !== null) {
            $result['params'] = $this->params;
        }

        return $result;
    }
}
