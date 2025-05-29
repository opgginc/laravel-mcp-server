<?php

namespace OPGG\LaravelMcpServer\Transports;

use Exception;

/**
 * Interface TransportInterface
 *
 * @see https://modelcontextprotocol.io/docs/concepts/transports
 */
interface TransportInterface
{
    /**
     * Start the transport connection.
     *
     * @throws Exception If the transport fails to start due to connection issues or configuration problems
     */
    public function start(): void;

    /**
     * Send a message through the transport.
     *
     * @param  string|array  $message  The message to send (will be JSON-encoded if array)
     *
     * @throws Exception If the message cannot be sent
     */
    public function send(string|array $message): void;

    /**
     * Close the transport connection.
     *
     * @throws Exception If the connection cannot be properly closed
     */
    public function close(): void;

    /**
     * Register a handler for connection close events.
     *
     * @param  callable  $handler  Function to call when the connection is closed
     */
    public function onClose(callable $handler): void;

    /**
     * Register a handler for error events.
     *
     * @param  callable  $handler  Function to call when an error occurs
     */
    public function onError(callable $handler): void;

    /**
     * Initialize the transport before starting the connection.
     *
     * @throws Exception If initialization fails due to missing dependencies or invalid configuration
     */
    public function initialize(): void;

    /**
     * Check if the transport is connected.
     *
     * @return bool True if the transport is connected, false otherwise
     */
    public function isConnected(): bool;

    /**
     * Receive messages from the transport.
     *
     * @return mixed The received message data
     *
     * @throws Exception If there is an error receiving messages
     */
    public function receive(): mixed;

    /**
     * Push a message to a specific client.
     *
     * @param  string  $clientId  The unique identifier of the target client
     * @param  array  $message  The message data to send
     *
     * @throws Exception If the message cannot be pushed to the client
     */
    public function pushMessage(string $clientId, array $message): void;
}
