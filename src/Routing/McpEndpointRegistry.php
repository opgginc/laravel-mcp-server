<?php

namespace OPGG\LaravelMcpServer\Routing;

use Illuminate\Support\Str;
use OPGG\LaravelMcpServer\Services\ToolService\EndpointToolCatalog;

final class McpEndpointRegistry
{
    /**
     * @var array<string, McpEndpointDefinition>
     */
    private array $definitions = [];

    public function create(string $path): McpEndpointDefinition
    {
        $id = (string) Str::uuid();
        $definition = McpEndpointDefinition::create($id, $path);
        $this->definitions[$id] = $definition;

        return $definition;
    }

    public function update(McpEndpointDefinition $definition): void
    {
        $this->definitions[$definition->id] = $definition;
    }

    public function remove(string $id): void
    {
        unset($this->definitions[$id]);
    }

    public function find(string $id): ?McpEndpointDefinition
    {
        return $this->definitions[$id] ?? null;
    }

    /**
     * @return array<string, McpEndpointDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<int, class-string>
     */
    public function allToolClasses(): array
    {
        $tools = [];
        /** @var EndpointToolCatalog $toolCatalog */
        $toolCatalog = app(EndpointToolCatalog::class);

        foreach ($this->definitions as $definition) {
            foreach ($toolCatalog->declaredToolClasses($definition) as $toolClass) {
                $tools[] = $toolClass;
            }
        }

        return array_values(array_unique($tools));
    }
}
