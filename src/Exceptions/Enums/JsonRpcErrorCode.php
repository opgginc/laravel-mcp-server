<?php

namespace OPGG\LaravelMcpServer\Exceptions\Enums;

enum JsonRpcErrorCode: int
{
    case PARSE_ERROR = -32700; // Invalid JSON was received by the server. An error occurred on the server while parsing the JSON text.
    case INVALID_REQUEST = -32600; // The JSON sent is not a valid Request object.
    case METHOD_NOT_FOUND = -32601; // 	The method does not exist / is not available.
    case INVALID_PARAMS = -32602; // Invalid method parameter(s).
    case INTERNAL_ERROR = -32603; // Internal JSON-RPC error.

    /**
     * Reserved for implementation-defined server-errors.
     */
    case SERVER_ERROR_START = -32099;
    case SERVER_ERROR_END = -32000;
}
