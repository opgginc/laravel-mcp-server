<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use Illuminate\Container\Container;
use InvalidArgumentException;
use stdClass;

/**
 * Manages the registration and retrieval of tools available to the MCP server.
 * Tools must implement the ToolInterface.
 *
 * @see [https://modelcontextprotocol.io/docs/concepts/tools](https://modelcontextprotocol.io/docs/concepts/tools)
 */
class ToolRepository
{
    /**
     * Class-level schema cache shared across repository instances.
     *
     * @var array<class-string<ToolInterface>, array<string, mixed>>
     */
    private static array $toolSchemaCacheByClass = [];

    /**
     * Holds the registered tool instances, keyed by their name.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

    /**
     * Holds precomputed tool schemas keyed by tool name.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $toolSchemas = [];

    /**
     * The Laravel service container instance.
     */
    protected Container $container;

    /**
     * Constructor.
     *
     * @param  Container|null  $container  The Laravel service container instance. If null, it resolves from the facade.
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * Clears class-level schema cache.
     * Primarily intended for test isolation.
     */
    public static function clearSchemaCache(): void
    {
        self::$toolSchemaCacheByClass = [];
    }

    /**
     * Registers multiple tools at once.
     *
     * @param  array<string|ToolInterface>  $tools  An array of tool class strings or ToolInterface instances.
     * @return $this The current ToolRepository instance for method chaining.
     *
     * @throws InvalidArgumentException If a tool does not implement ToolInterface.
     */
    public function registerMany(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }

        return $this;
    }

    /**
     * Registers multiple tools by schema only.
     * This avoids keeping executable tool instances when only tools/list is needed.
     *
     * @param  array<string|ToolInterface>  $tools  An array of tool class strings or ToolInterface instances.
     * @return $this
     */
    public function registerSchemaMany(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->registerSchema($tool);
        }

        return $this;
    }

    /**
     * Registers a single tool.
     * If a class string is provided, it resolves the tool from the container.
     *
     * @param  string|ToolInterface  $tool  The tool class string or a ToolInterface instance.
     * @return $this The current ToolRepository instance for method chaining.
     *
     * @throws InvalidArgumentException If the provided $tool is not a string or ToolInterface, or if the resolved object does not implement ToolInterface.
     */
    public function register(string|ToolInterface $tool): self
    {
        if (is_string($tool)) {
            $tool = $this->container->make($tool);
        }

        if (! $tool instanceof ToolInterface) {
            throw new InvalidArgumentException('Tool must implement the '.ToolInterface::class);
        }

        $this->tools[$tool->name()] = $tool;

        return $this;
    }

    /**
     * Registers a single tool schema.
     *
     * @param  string|ToolInterface  $tool  The tool class string or a ToolInterface instance.
     * @return $this
     */
    public function registerSchema(string|ToolInterface $tool): self
    {
        if (is_string($tool)) {
            $schema = self::$toolSchemaCacheByClass[$tool] ??= $this->resolveSchemaFromClass($tool);
            $this->toolSchemas[$schema['name']] = $schema;

            return $this;
        }

        $schema = $this->buildToolSchema($tool);
        $this->toolSchemas[$schema['name']] = $schema;

        return $this;
    }

    /**
     * Retrieves all registered tools.
     *
     * @return array<string, ToolInterface> An array of registered tool instances, keyed by their name.
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * Retrieves a specific tool by its name.
     *
     * @param  string  $name  The name of the tool to retrieve.
     * @return ToolInterface|null The tool instance if found, otherwise null.
     */
    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Generates an array of schemas for all registered tools, suitable for the MCP capabilities response.
     * Includes name, description, inputSchema, and optional annotations for each tool.
     *
     * @return array<int, array{name: string, description: string, inputSchema: array<string, mixed>, annotations?: array<string, mixed>}> An array of tool schemas.
     */
    public function getToolSchemas(): array
    {
        $schemasByName = $this->toolSchemas;
        foreach ($this->tools as $tool) {
            $schema = $this->buildToolSchema($tool);
            $schemasByName[$schema['name']] = $schema;
        }

        return array_values($schemasByName);
    }

    /**
     * @param  class-string  $toolClass
     * @return array<string, mixed>
     */
    private function resolveSchemaFromClass(string $toolClass): array
    {
        $tool = $this->container->make($toolClass);
        if (! $tool instanceof ToolInterface) {
            throw new InvalidArgumentException('Tool must implement the '.ToolInterface::class);
        }

        return $this->buildToolSchema($tool);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildToolSchema(ToolInterface $tool): array
    {
        $injectArray = [];
        if (empty($tool->inputSchema())) {
            // inputSchema cannot be empty, set a default value.
            $injectArray['inputSchema'] = [
                'type' => 'object',
                'properties' => new stdClass,
                'required' => [],
            ];
        }
        if (! empty($tool->annotations())) {
            $injectArray['annotations'] = $tool->annotations();
        }

        $schema = [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'inputSchema' => $tool->inputSchema(),
            ...$injectArray,
        ];

        // Optional metadata introduced in newer MCP schema revisions for richer discovery payloads.
        // @see https://modelcontextprotocol.io/specification/2025-11-25/schema
        if (method_exists($tool, 'title')) {
            $title = $tool->title();
            if (is_string($title) && $title !== '') {
                $schema['title'] = $title;
            }
        }

        if (method_exists($tool, 'icons')) {
            $icons = $tool->icons();
            if (is_array($icons) && $icons !== []) {
                $schema['icons'] = array_values($icons);
            }
        }

        if (method_exists($tool, 'outputSchema')) {
            $outputSchema = $tool->outputSchema();
            if (is_array($outputSchema) && $outputSchema !== []) {
                $schema['outputSchema'] = $outputSchema;
            }
        }

        if (method_exists($tool, 'execution')) {
            $execution = $tool->execution();
            if (is_array($execution) && $execution !== []) {
                $schema['execution'] = $execution;
            }
        }

        $meta = null;
        if (method_exists($tool, 'meta')) {
            $meta = $tool->meta();
        } elseif (method_exists($tool, '_meta')) {
            $meta = $tool->_meta();
        }

        if (is_array($meta) && $meta !== []) {
            $schema['_meta'] = $meta;
        }

        return $schema;
    }
}
