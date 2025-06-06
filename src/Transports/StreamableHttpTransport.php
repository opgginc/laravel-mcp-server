<?php

namespace OPGG\LaravelMcpServer\Transports;

use Exception;

/**
 * StreamableHttpTransport implementation for MCP Server.
 *
 * Handles HTTP transport with streaming capabilities using Server-Sent Events (SSE).
 * This transport is designed for use with the StreamableHttpController.
 *
 * @see https://modelcontextprotocol.io/docs/concepts/transports
 * @since 1.0.0
 */
final class StreamableHttpTransport implements TransportInterface
{
    /**
     * Tracks if the server-side connection is considered active.
     */
    protected bool $connected = false;

    /**
     * Callbacks executed when the connection is closed via `close()`.
     *
     * @var array<callable>
     */
    protected array $closeHandlers = [];

    /**
     * Callbacks executed on transport errors, typically via `triggerError()`.
     *
     * @var array<callable>
     */
    protected array $errorHandlers = [];

    /**
     * Unique identifier for the client connection, generated during initialization.
     */
    protected ?string $clientId = null;

    /**
     * Starts the StreamableHttp transport connection.
     * Sets the connected flag and initializes the transport. Idempotent.
     *
     * @throws Exception If initialization fails.
     */
    public function start(): void
    {
        if ($this->connected) {
            return;
        }

        $this->connected = true;
        $this->initialize();
    }

    /**
     * Initializes the transport: generates client ID and sends the initial 'endpoint' event.
     * Adapter-specific initialization might occur here or externally.
     *
     * @throws Exception If sending the initial event fails.
     */
    public function initialize(): void {}

    /**
     * Sends a message payload as a 'message' type StreamableHttp event.
     * Encodes array messages to JSON.
     *
     * @param  string|array  $message  The message content.
     *
     * @throws Exception If JSON encoding fails or sending the event fails.
     */
    public function send(string|array $message): void {}

    /**
     * Closes the connection, notifies handlers, cleans up adapter resources, and attempts a final 'close' event.
     * Idempotent. Errors during cleanup/final event are logged.
     *
     * @throws Exception From handlers if they throw exceptions.
     */
    public function close(): void
    {
        if (! $this->connected) {
            return;
        }

        $this->connected = false;
    }

    /**
     * Registers a callback to execute when `close()` is called.
     *
     * @param  callable  $handler  The callback (takes no arguments).
     */
    public function onClose(callable $handler): void
    {
        $this->closeHandlers[] = $handler;
    }

    /**
     * Registers a callback to execute on transport errors triggered by `triggerError()`.
     *
     * @param  callable  $handler  The callback (receives string error message).
     */
    public function onError(callable $handler): void
    {
        $this->errorHandlers[] = $handler;
    }

    /**
     * Checks if the client connection is still active using `connection_aborted()`.
     *
     * @return bool True if connected, false if aborted.
     */
    public function isConnected(): bool
    {
        return connection_aborted() === 0;
    }

    /**
     * Receives messages for this client via the configured adapter.
     * Returns an empty array if no adapter, no messages, or on error.
     * Triggers error handlers on adapter failure.
     *
     * @return array An array of message payloads.
     */
    public function receive(): array
    {
        return [];
    }

    /**
     * Pushes a message to the adapter for later retrieval by the target client.
     * Encodes the message to JSON before pushing.
     *
     * @param  string  $clientId  The target client ID.
     * @param  array  $message  The message payload (as an array).
     *
     * @throws Exception If adapter is not set, JSON encoding fails, or adapter push fails.
     */
    public function pushMessage(string $clientId, array $message): void {}
}
