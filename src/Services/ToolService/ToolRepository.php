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
     * Holds the registered tool instances, keyed by their name.
     *
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

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
     * Includes name, description, inputSchema, optional outputSchema, and optional annotations for each tool.
     *
     * @return array<int, array{name: string, description: string, inputSchema: array<string, mixed>, outputSchema?: array<string, mixed>, annotations?: array<string, mixed>}> An array of tool schemas.
     */
    public function getToolSchemas(): array
    {
        $schemas = [];
        foreach ($this->tools as $tool) {
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

            // Add output schema if the tool supports it
            if (method_exists($tool, 'outputSchema') && $tool->outputSchema() !== null) {
                $injectArray['outputSchema'] = $tool->outputSchema();
            }

            $schemas[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
                ...$injectArray,
            ];
        }

        return $schemas;
    }
}
