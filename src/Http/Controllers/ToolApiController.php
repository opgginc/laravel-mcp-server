<?php

namespace OPGG\LaravelMcpServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
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
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
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
        $enabledEndpoints = $this->enabledEndpoints();
        $endpointStates = $this->endpointStates($enabledEndpoints);
        try {
            $payloadArguments = $this->parsePayloadArguments($request);
        } catch (JsonException|HttpFoundationJsonException) {
            if (! $this->shouldIgnorePayloadException($toolName, $queryParameters, $endpointStates)) {
                return response()->json([
                    'message' => 'Parse error',
                    'code' => JsonRpcErrorCode::PARSE_ERROR->value,
                ], Response::HTTP_BAD_REQUEST);
            }

            $payloadArguments = [];
        } catch (\InvalidArgumentException $exception) {
            if (! $this->shouldIgnorePayloadException($toolName, $queryParameters, $endpointStates)) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'code' => JsonRpcErrorCode::INVALID_REQUEST->value,
                ], Response::HTTP_BAD_REQUEST);
            }

            $payloadArguments = [];
        }

        $sessionId = $this->sessionId($request);

        foreach ($enabledEndpoints as $endpoint) {
            $endpointState = $endpointStates[$endpoint->id];
            $arguments = $this->mergeArguments(
                payloadArguments: $payloadArguments,
                queryParameters: $queryParameters,
                consumedQueryParameters: $endpointState['consumedQueryParameters'],
            );
            $message = $this->toolExecuteMessage($toolName, $arguments);
            $toolResolutionContext = new ToolResolutionContext(
                queryParameters: $queryParameters,
                requestMessage: $message,
            );

            if (! $this->endpointToolCatalog->toolClassesContainName(
                $endpointState['declaredToolClasses'],
                $toolName,
            )) {
                continue;
            }

            $visibleToolClasses = $this->endpointToolCatalog->visibleToolClasses(
                $endpoint,
                $toolResolutionContext,
                $endpointState['resolver'],
            );
            if (! $this->endpointToolCatalog->toolClassesContainName($visibleToolClasses, $toolName)) {
                return $this->toolNotFoundResponse($toolName);
            }

            $server = $this->serverFactory->make(
                endpoint: $endpoint,
                requestMessage: $message,
                toolResolutionContext: $toolResolutionContext,
                resolvedToolClasses: $visibleToolClasses,
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
    private function mergeArguments(
        array $payloadArguments,
        array $queryParameters,
        array $consumedQueryParameters,
    ): array {
        $queryArguments = $queryParameters;
        foreach ($consumedQueryParameters as $parameterName) {
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
     * @param  array<string, array{
     *   resolver: DynamicToolResolverInterface|null,
     *   declaredToolClasses: array<int, class-string<ToolInterface>>,
     *   consumedQueryParameters: array<int, string>
     * }>  $endpointStates
     */
    private function shouldIgnorePayloadException(string $toolName, array $queryParameters, array $endpointStates): bool
    {
        if ($queryParameters === []) {
            return false;
        }

        foreach ($endpointStates as $endpointState) {
            if (! $this->endpointToolCatalog->toolClassesContainName(
                $endpointState['declaredToolClasses'],
                $toolName,
            )) {
                continue;
            }

            return $this->mergeArguments([], $queryParameters, $endpointState['consumedQueryParameters']) !== [];
        }

        return false;
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
     * @param  array<int, McpEndpointDefinition>  $endpoints
     * @return array<string, array{
     *   resolver: DynamicToolResolverInterface|null,
     *   declaredToolClasses: array<int, class-string<ToolInterface>>,
     *   consumedQueryParameters: array<int, string>
     * }>
     */
    private function endpointStates(array $endpoints): array
    {
        $states = [];
        foreach ($endpoints as $endpoint) {
            $resolver = $this->endpointToolCatalog->resolverForEndpoint($endpoint);
            $states[$endpoint->id] = [
                'resolver' => $resolver,
                'declaredToolClasses' => $this->endpointToolCatalog->declaredToolClasses($endpoint, $resolver),
                'consumedQueryParameters' => $this->endpointToolCatalog->consumedQueryParameters($endpoint, $resolver),
            ];
        }

        return $states;
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

    private function toolNotFoundResponse(string $toolName): JsonResponse
    {
        return response()->json([
            'message' => "Tool '{$toolName}' not found",
            'code' => JsonRpcErrorCode::METHOD_NOT_FOUND->value,
        ], Response::HTTP_NOT_FOUND);
    }
}
