<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonException;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
use Symfony\Component\HttpFoundation\Response;

class ToolApiController
{
    public function __construct(
        private readonly McpEndpointRegistry $endpointRegistry,
        private readonly McpServerFactory $serverFactory,
    ) {}

    public function handle(Request $request, string $tool_name)
    {
        $toolName = trim($tool_name);
        if ($toolName === '') {
            return response()->json([
                'message' => 'Tool name is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $arguments = $this->parseArguments($request);
        } catch (JsonException) {
            return response()->json([
                'message' => 'Parse error',
                'code' => JsonRpcErrorCode::PARSE_ERROR->value,
            ], Response::HTTP_BAD_REQUEST);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'code' => JsonRpcErrorCode::INVALID_REQUEST->value,
            ], Response::HTTP_BAD_REQUEST);
        }

        $sessionId = $this->sessionId($request);

        foreach ($this->enabledEndpoints() as $endpoint) {
            $message = $this->toolExecuteMessage($toolName, $arguments);
            $server = $this->serverFactory->make($endpoint, $message);
            $responseData = $server->requestMessage(clientId: $sessionId, message: $message)->toArray();

            $error = $responseData['error'] ?? null;
            if (! is_array($error)) {
                $result = $responseData['result']['result'] ?? null;

                return response()->json($result);
            }

            $errorCode = $error['code'] ?? null;
            if ($errorCode === JsonRpcErrorCode::METHOD_NOT_FOUND->value) {
                continue;
            }

            return response()->json($error, $this->errorHttpStatus($errorCode));
        }

        return response()->json([
            'message' => "Tool '{$toolName}' not found",
            'code' => JsonRpcErrorCode::METHOD_NOT_FOUND->value,
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseArguments(Request $request): array
    {
        $queryArguments = $this->parseQueryArguments($request);
        if ($queryArguments !== []) {
            return $queryArguments;
        }

        $formArguments = $request->request->all();
        if ($formArguments !== []) {
            return $formArguments;
        }

        $content = trim($request->getContent());
        if ($content !== '') {
            $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new \InvalidArgumentException('Request body must be a JSON object or array.');
            }

            return $decoded;
        }

        return [];
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseQueryArguments(Request $request): array
    {
        $queryArguments = $request->query->all();

        $queryString = $request->server('QUERY_STRING');
        if (! is_string($queryString) || trim($queryString) === '') {
            return $queryArguments;
        }

        $valuesByKey = [];
        foreach (explode('&', $queryString) as $segment) {
            if ($segment === '') {
                continue;
            }

            $parts = explode('=', $segment, 2);
            $key = urldecode($parts[0]);
            if ($key === '' || str_ends_with($key, '[]') || str_contains($key, '[')) {
                continue;
            }

            $value = urldecode($parts[1] ?? '');
            $valuesByKey[$key][] = $value;
        }

        foreach ($valuesByKey as $key => $values) {
            if (count($values) <= 1) {
                continue;
            }

            $queryArguments[$key] = $values;
        }

        return $queryArguments;
    }

    private function sessionId(Request $request): string
    {
        $headerValue = $request->headers->get('mcp-session-id');
        if (is_string($headerValue) && Str::isUuid($headerValue)) {
            return $headerValue;
        }

        return Str::uuid()->toString();
    }

    /**
     * @return array<int, McpEndpointDefinition>
     */
    private function enabledEndpoints(): array
    {
        $endpoints = [];
        foreach ($this->endpointRegistry->all() as $endpoint) {
            if (! $endpoint->enabledApi) {
                continue;
            }

            $endpoints[] = $endpoint;
        }

        return $endpoints;
    }

    /**
     * @param  array<int|string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function toolExecuteMessage(string $toolName, array $arguments): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => Str::uuid()->toString(),
            'method' => 'tools/execute',
            'params' => [
                'name' => $toolName,
                'arguments' => $arguments,
            ],
        ];
    }

    private function errorHttpStatus(mixed $errorCode): int
    {
        if (! is_int($errorCode)) {
            return Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        return match ($errorCode) {
            JsonRpcErrorCode::PARSE_ERROR->value,
            JsonRpcErrorCode::INVALID_REQUEST->value,
            JsonRpcErrorCode::INVALID_PARAMS->value => Response::HTTP_BAD_REQUEST,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}
