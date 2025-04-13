<?php

namespace OPGG\LaravelMcpServer\Exceptions;

use Exception;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;

/**
 * Represents a JSON-RPC error exception.
 *
 * This exception is thrown when an error occurs during JSON-RPC request processing.
 * It adheres to the JSON-RPC 2.0 specification for error objects.
 */
class JsonRpcErrorException extends Exception
{
    /**
     * The JSON-RPC error code.
     *
     * @var JsonRpcErrorCode
     */
    private JsonRpcErrorCode $jsonRpcErrorCode;

    /**
     * Additional data associated with the error.
     *
     * @var mixed|null
     */
    private mixed $errorData;

    /**
     * JsonRpcErrorException constructor.
     *
     * @param string $message A human-readable description of the error.
     * @param JsonRpcErrorCode $code The JSON-RPC error code enum value.
     * @param mixed|null $data Additional data associated with the error (optional).
     */
    public function __construct(string $message, JsonRpcErrorCode $code, mixed $data = null)
    {
        parent::__construct($message, $code->value);
        $this->jsonRpcErrorCode = $code;
        $this->errorData = $data;
    }

    /**
     * Get the additional data associated with the error.
     *
     * @return mixed|null The error data, or null if not set.
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Get the JSON-RPC error code.
     *
     * @return int The integer value of the JSON-RPC error code.
     */
    public function getJsonRpcErrorCode(): int
    {
        return $this->jsonRpcErrorCode->value;
    }

    /**
     * Convert the exception to a JSON-RPC error object array.
     *
     * @return array{code: int, message: string, data?: mixed} The error object representation.
     */
    public function toArray(): array
    {
        $error = [
            'code' => $this->jsonRpcErrorCode->value,
            'message' => $this->message,
        ];

        if ($this->errorData !== null) {
            $error['data'] = $this->errorData;
        }

        return $error;
    }
}
