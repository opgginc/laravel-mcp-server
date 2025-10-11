<?php

namespace OPGG\LaravelMcpServer\Services\ToolService\Examples;

use Illuminate\Support\Facades\App;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use stdClass;

class VersionCheckTool implements ToolInterface
{
    public function isStreaming(): bool
    {
        return false;
    }

    public function title(): ?string
    {
        return 'Laravel Version Check';
    }

    public function name(): string
    {
        return 'check-version';
    }

    public function description(): string
    {
        return 'Check the current Laravel version.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new stdClass,
            'required' => [],
        ];
    }

    /**
     * Communicates the scalar string response format so MCP 2025-06-18 clients
     * can validate tool executions before exposing them to an end user.
     *
     * @see https://modelcontextprotocol.io/specification/2025-06-18/server/tools#tool-result
     */
    public function outputSchema(): array
    {
        return [
            'type' => 'string',
            'description' => 'Plain-text summary of the current Laravel version and timestamp.',
        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): string
    {
        $now = now()->format('Y-m-d H:i:s');
        $version = App::version();

        return "current Version: {$version} - {$now}";
    }
}
