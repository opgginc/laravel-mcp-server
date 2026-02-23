<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Str;
use JsonException;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcErrorResource;
use OPGG\LaravelMcpServer\Data\Resources\JsonRpc\JsonRpcResultResource;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Routing\McpRouteRegistrar;
use OPGG\LaravelMcpServer\Server\McpServerFactory;

class StreamableHttpController
{
    public function __construct(
        private readonly McpEndpointRegistry $endpointRegistry,
        private readonly McpServerFactory $serverFactory,
    ) {}

    public function getHandle(Request $request)
    {
        return response('', 405);
    }

    public function postHandle(Request $request)
    {
        $endpointId = $this->resolveEndpointId($request);
        if ($endpointId === null) {
            return $this->jsonRpcErrorResponse(
                message: 'Bad Request: MCP endpoint is not configured.',
                code: JsonRpcErrorCode::INVALID_REQUEST,
            );
        }

        $endpoint = $this->resolveEndpoint($request, $endpointId);
        if ($endpoint === null) {
            return $this->jsonRpcErrorResponse(
                message: 'Bad Request: MCP endpoint is not registered.',
                code: JsonRpcErrorCode::INVALID_REQUEST,
            );
        }

        try {
            $messageJson = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->jsonRpcErrorResponse(
                message: 'Parse error',
                code: JsonRpcErrorCode::PARSE_ERROR,
            );
        }

        $server = $this->serverFactory->make($endpoint, $messageJson);

        $mcpSessionId = $this->resolveSessionId($request);

        $processMessageData = $server->requestMessage(clientId: $mcpSessionId, message: $messageJson);

        // MCP specification: notifications should return HTTP 202 with no body
        if ($processMessageData->isNotification) {
            return response('', 202);
        }

        if ($processMessageData->resource instanceof JsonRpcResultResource || $processMessageData->resource instanceof JsonRpcErrorResource) {
            return response()->json($processMessageData->resource->toResponse());
        }

        return $this->jsonRpcErrorResponse(
            message: 'Bad Request: invalid session ID or method.',
            code: JsonRpcErrorCode::INVALID_REQUEST,
        );
    }

    private function resolveEndpointId(Request $request): ?string
    {
        $routeParameter = $request->route(McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY);
        if (is_string($routeParameter) && $routeParameter !== '') {
            return $routeParameter;
        }

        /** @var mixed $routeInfo */
        $routeInfo = $request->route();
        if ($routeInfo instanceof LaravelRoute) {
            $actionEndpointId = $routeInfo->getAction(McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY);

            return is_string($actionEndpointId) && $actionEndpointId !== '' ? $actionEndpointId : null;
        }

        if (! is_array($routeInfo)) {
            return null;
        }

        $action = $routeInfo[2] ?? null;
        if (! is_array($action)) {
            return null;
        }

        $endpointId = $action[McpRouteRegistrar::ROUTE_DEFAULT_ENDPOINT_KEY] ?? null;

        return is_string($endpointId) && $endpointId !== '' ? $endpointId : null;
    }

    private function resolveEndpoint(Request $request, string $endpointId): ?McpEndpointDefinition
    {
        $endpoint = $this->endpointRegistry->find($endpointId);
        if ($endpoint instanceof McpEndpointDefinition) {
            return $endpoint;
        }

        $endpointPayload = $this->resolveEndpointDefinitionPayload($request);
        if ($endpointPayload === null) {
            return null;
        }

        try {
            $restoredEndpoint = McpEndpointDefinition::fromArray($endpointPayload);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($restoredEndpoint->id !== $endpointId) {
            return null;
        }

        $this->endpointRegistry->update($restoredEndpoint);

        return $restoredEndpoint;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveEndpointDefinitionPayload(Request $request): ?array
    {
        /** @var mixed $routeInfo */
        $routeInfo = $request->route();
        if ($routeInfo instanceof LaravelRoute) {
            $actionDefinition = $routeInfo->getAction(McpRouteRegistrar::ROUTE_ENDPOINT_DEFINITION_KEY);

            return is_array($actionDefinition) ? $actionDefinition : null;
        }

        if (! is_array($routeInfo)) {
            return null;
        }

        $action = $routeInfo[2] ?? null;
        if (! is_array($action)) {
            return null;
        }

        $endpointDefinition = $action[McpRouteRegistrar::ROUTE_ENDPOINT_DEFINITION_KEY] ?? null;

        return is_array($endpointDefinition) ? $endpointDefinition : null;
    }

    private function resolveSessionId(Request $request): string
    {
        $sessionId = $request->headers->get('mcp-session-id');
        if (is_string($sessionId) && Str::isUuid($sessionId)) {
            return $sessionId;
        }

        return Str::uuid()->toString();
    }

    private function jsonRpcErrorResponse(string $message, JsonRpcErrorCode $code)
    {
        $error = new JsonRpcErrorResource(
            new JsonRpcErrorException($message, $code)
        );

        return response()->json($error->toResponse(), 400);
    }
}
