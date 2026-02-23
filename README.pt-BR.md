<h1 align="center">Servidor Laravel MCP por OP.GG</h1>

<p align="center">
  Construa um servidor MCP de primeira rota em Laravel e Lumen
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Status da versão"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total de downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Última versão estável"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Site Oficial</a>
</p>

<p align="center">
  <a href="README.md">Inglês</a> |
  <a href="README.pt-BR.md">Português do Brasil</a> |
  <a href="README.ko.md">Coreano</a> |
  <a href="README.ru.md">Inglês</a> |
  <a href="README.zh-CN.md">Chinês simplificado</a> |
  <a href="README.zh-TW.md">繁體中文</a> |
  <a href="README.pl.md">Polês</a> |
  <a href="README.es.md">Espanhol</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## Quebrando alterações 2.0.0

- A configuração do endpoint passou de registro orientado por configuração para registro orientado por rota.
- HTTP streamable é o único transporte suportado.
- Os mutadores de metadados do servidor são consolidados em `setServerInfo(...)`.
- Os métodos de transporte de ferramentas legados foram removidos do tempo de execução (`messageType()`, `ProcessMessageType::SSE`).

Guia completo de migração: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Visão geral

O Laravel MCP Server fornece registro de endpoint MCP baseado em rota para Laravel e Lumen.

Pontos principais:
- Transporte HTTP streamável
- Configuração de primeira rota (`Route::mcp(...)` / `McpRoute::register(...)`)
- Ferramenta, recurso, modelo de recurso e registro imediato por endpoint
- Metadados de endpoint compatíveis com cache de rota

## Requisitos

-PHP >= 8.2
- Laravel (Iluminar) >= 9.x
- Lúmen >= 9.x (opcional)

## Início rápido

### 1) Instalar

```bash
composer require opgginc/laravel-mcp-server
```

### 2) Registre um endpoint (Laravel)

```php
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
        VersionCheckTool::class,
    ]);
```

### 3) Verifique

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

Verificação rápida de JSON-RPC:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## Configuração de lúmen

```php
// bootstrap/app.php
$app->withFacades();
$app->withEloquent();
$app->register(OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider::class);
```

```php
use OPGG\LaravelMcpServer\Routing\McpRoute;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;

McpRoute::register('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
    ]);
```

## Segurança Mínima (Produção)

Use o middleware Laravel em seu grupo de rotas MCP.

```php
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'throttle:100,1',
])->group(function (): void {
    Route::mcp('/mcp')
        ->setServerInfo(
            name: 'Secure MCP',
            version: '2.0.0',
        )
        ->tools([
            \App\MCP\Tools\MyCustomTool::class,
        ]);
});
```

## Notas de migração v2.0.0 (da v1.0.0)

- Configuração do endpoint MCP movida de configuração para registro de rota.
- HTTP streamable é o único transporte.
- Os mutadores de metadados do servidor são consolidados em `setServerInfo(...)`.
- O comando de migração de ferramentas está disponível para assinaturas legadas:

```bash
php artisan mcp:migrate-tools
```

Guia completo: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## Recursos avançados (links rápidos)

- Criar ferramentas: `php artisan make:mcp-tool ToolName`
- Crie recursos: `php artisan make:mcp-resource ResourceName`
- Crie modelos de recursos: `php artisan make:mcp-resource-template TemplateName`
- Crie prompts: `php artisan make:mcp-prompt PromptName`
- Crie notificações: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- Gere a partir do OpenAPI: `php artisan make:swagger-mcp-tool <spec-url-or-file>`

Referências de código:
- Exemplos de ferramentas: `src/Services/ToolService/Examples/`
- Exemplos de recursos: `src/Services/ResourceService/Examples/`
- Serviço de prompt: `src/Services/PromptService/`
- Manipuladores de notificação: `src/Server/Notification/`
- Construtor de rotas: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> Ferramenta MCP

Gere ferramentas MCP a partir de uma especificação Swagger/OpenAPI:

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Opções úteis:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `caminho` ou `none`
- `--prefix`: prefixo do nome da classe para ferramentas/recursos gerados
- `--test-api`: testa a conectividade do endpoint antes da geração

Comportamento de geração:
- No modo interativo, você pode escolher Ferramenta ou Recurso por endpoint.
- No modo não interativo, os endpoints `GET` são gerados como Recursos e outros métodos como Ferramentas.

### Visualização interativa aprimorada

Se você executar o comando sem `--group-by`, o gerador mostrará uma visualização interativa da estrutura de pastas e contagens de arquivos antes da criação.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

Exemplo de saída de visualização:

```text
Choose how to organize your generated tools and resources:

Tag-based grouping (organize by OpenAPI tags)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Pet, Tools/Store, Tools/User

Path-based grouping (organize by API path)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Api, Tools/Users, Tools/Orders

No grouping (everything in root folder)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/, Resources/
```

Após a geração, registre as classes de ferramentas geradas em seu terminal MCP:

```php
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Generated MCP Server',
        version: '2.0.0',
    )
    ->tools([
        \App\MCP\Tools\Billing\CreateInvoiceTool::class,
        \App\MCP\Tools\Billing\UpdateInvoiceTool::class,
    ]);
```

## Exemplo de classe de ferramenta

```php
<?php

namespace App\MCP\Tools;

use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GreetingTool implements ToolInterface
{
    public function name(): string
    {
        return 'greeting-tool';
    }

    public function description(): string
    {
        return 'Return a greeting message.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
        ];
    }

    public function execute(array $arguments): mixed
    {
        return [
            'message' => 'Hello '.$arguments['name'],
        ];
    }
}
```

## Exemplo de classe de prompt

```php
<?php

namespace App\MCP\Prompts;

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';

    public ?string $description = 'Generate a welcome message.';

    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'User name',
            'required' => true,
        ],
    ];

    public string $text = 'Welcome, {username}!';
}
```

## Exemplo de classe de recurso

```php
<?php

namespace App\MCP\Resources;

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

class BuildInfoResource extends Resource
{
    public string $uri = 'app://build-info';

    public string $name = 'Build Info';

    public ?string $mimeType = 'application/json';

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode([
                'version' => '2.0.0',
                'environment' => app()->environment(),
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
```

## Registrar exemplos em uma rota

```php
use App\MCP\Prompts\WelcomePrompt;
use App\MCP\Resources\BuildInfoResource;
use App\MCP\Tools\GreetingTool;
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Example MCP Server',
        version: '2.0.0',
    )
    ->tools([GreetingTool::class])
    ->resources([BuildInfoResource::class])
    ->prompts([WelcomePrompt::class]);
```

## Comandos de teste e qualidade

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## Tradução

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Traduzir idiomas selecionados:

```bash
python scripts/translate_readme.py es ko
```

## Licença

Este projeto é distribuído sob a licença do MIT.
