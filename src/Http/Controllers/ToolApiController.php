<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonException;
use OPGG\LaravelMcpServer\Data\ToolResolutionContext;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Routing\McpEndpointDefinition;
use OPGG\LaravelMcpServer\Routing\McpEndpointRegistry;
use OPGG\LaravelMcpServer\Server\McpServerFactory;
use OPGG\LaravelMcpServer\Services\ToolService\DynamicToolResolverInterface;
use OPGG\LaravelMcpServer\Services\ToolService\EndpointToolCatalog;
use OPGG\LaravelMcpServer\Utils\RequestQueryParameterUtil;
use Symfony\Component\HttpFoundation\Exception\JsonException as HttpFoundationJsonException;
use Symfony\Component\HttpFoundation\Response;

class ToolApiController
{
    public function __construct(
        private readonly McpEndpointRegistry $endpointRegistry,
        private readonly McpServerFactory $serverFactory,
        private readonly EndpointToolCatalog $endpointToolCatalog,
    ) {}

    public function handle(Request $request, string $tool_name)
    {
        $toolName = trim($tool_name);
        if ($toolName === '') {
            return response()->json([
                'message' => 'Tool name is required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $queryParameters = RequestQueryParameterUtil::all($request);
        try {
            $payloadArguments = $this->parsePayloadArguments($request);
        } catch (JsonException|HttpFoundationJsonException) {
            if (! $this->shouldIgnorePayloadException($toolName, $queryParameters)) {
                return response()->json([
                    'message' => 'Parse error',
                    'code' => JsonRpcErrorCode::PARSE_ERROR->value,
                ], Response::HTTP_BAD_REQUEST);
            }

            $payloadArguments = [];
        } catch (\InvalidArgumentException $exception) {
            if (! $this->shouldIgnorePayloadException($toolName, $queryParameters)) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'code' => JsonRpcErrorCode::INVALID_REQUEST->value,
                ], Response::HTTP_BAD_REQUEST);
            }

            $payloadArguments = [];
        }

        $sessionId = $this->sessionId($request);

        foreach ($this->enabledEndpoints() as $endpoint) {
            $arguments = $this->mergeArguments(
                payloadArguments: $payloadArguments,
                queryParameters: $queryParameters,
                endpoint: $endpoint,
            );
            $message = $this->toolExecuteMessage($toolName, $arguments);
            $toolResolutionContext = new ToolResolutionContext(
                queryParameters: $queryParameters,
                requestMessage: $message,
            );

            if (! $this->endpointToolCatalog->declaresToolName($endpoint, $toolName)) {
                continue;
            }

            if (! $this->endpointToolCatalog->exposesToolName($endpoint, $toolName, $toolResolutionContext)) {
                return $this->toolNotFoundResponse($toolName);
            }

            $server = $this->serverFactory->make(
                endpoint: $endpoint,
                requestMessage: $message,
                toolResolutionContext: $toolResolutionContext,
            );
            $responseData = $server->requestMessage(clientId: $sessionId, message: $message)->toArray();

            $error = $responseData['error'] ?? null;
            if (! is_array($error)) {
                $result = $responseData['result']['result'] ?? null;

                return response()->json($result);
            }

            $errorCode = $error['code'] ?? null;
            if ($errorCode === JsonRpcErrorCode::METHOD_NOT_FOUND->value) {
                return response()->json($error, Response::HTTP_NOT_FOUND);
            }

            return response()->json($error, $this->errorHttpStatus($errorCode));
        }

        return $this->toolNotFoundResponse($toolName);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parsePayloadArguments(Request $request): array
    {
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
    private function mergeArguments(array $payloadArguments, array $queryParameters, McpEndpointDefinition $endpoint): array
    {
        $queryArguments = $queryParameters;
        foreach ($this->consumedQueryParameters($endpoint) as $parameterName) {
            unset($queryArguments[$parameterName]);
        }

        if ($queryArguments === []) {
            return $payloadArguments;
        }

        return array_replace($payloadArguments, $queryArguments);
    }

    /**
     * Preserve legacy query-precedence behavior only when the selected endpoint
     * still receives at least one real tool argument after removing filter-only keys.
     *
     * @param  array<int|string, mixed>  $queryParameters
     */
    private function shouldIgnorePayloadException(string $toolName, array $queryParameters): bool
    {
        if ($queryParameters === []) {
            return false;
        }

        foreach ($this->enabledEndpoints() as $endpoint) {
            if (! $this->endpointToolCatalog->declaresToolName($endpoint, $toolName)) {
                continue;
            }

            return $this->mergeArguments([], $queryParameters, $endpoint) !== [];
        }

        return true;
    }

    /**
     * Dynamic resolvers can expose public consumedQueryParameters() to reserve
     * endpoint-level selectors such as "phase" for filtering only.
     *
     * @return array<int, string>
     */
    private function consumedQueryParameters(McpEndpointDefinition $endpoint): array
    {
        $resolverClass = $endpoint->dynamicToolsResolver;
        if ($resolverClass === null || ! is_a($resolverClass, DynamicToolResolverInterface::class, true)) {
            return [];
        }

        $resolver = app()->make($resolverClass);
        if (! $resolver instanceof DynamicToolResolverInterface) {
            return [];
        }

        $callable = [$resolver, 'consumedQueryParameters'];
        if (! is_callable($callable)) {
            return [];
        }

        $parameterNames = call_user_func($callable);
        if (! is_array($parameterNames)) {
            return [];
        }

        $normalizedParameterNames = [];
        foreach ($parameterNames as $parameterName) {
            if (! is_string($parameterName) || $parameterName === '') {
                continue;
            }

            $normalizedParameterNames[$parameterName] = $parameterName;
        }

        return array_values($normalizedParameterNames);
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

    private function toolNotFoundResponse(string $toolName)
    {
        return response()->json([
            'message' => "Tool '{$toolName}' not found",
            'code' => JsonRpcErrorCode::METHOD_NOT_FOUND->value,
        ], Response::HTTP_NOT_FOUND);
    }
}
