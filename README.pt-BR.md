<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Um poderoso pacote Laravel para construir um Model Context Protocol Server de forma integrada
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Site Oficial</a>
</p>

<p align="center">
  <a href="README.md">English</a> |
  <a href="README.pt-BR.md">Português do Brasil</a> |
  <a href="README.ko.md">한국어</a> |
  <a href="README.ru.md">Русский</a> |
  <a href="README.zh-CN.md">简体中文</a> |
  <a href="README.zh-TW.md">繁體中文</a> |
  <a href="README.pl.md">Polski</a> |
  <a href="README.es.md">Español</a>
</p>

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## ⚠️ Informações de Versão & Breaking Changes

### Mudanças na v2.0.0 (Versão Atual) ✅

A versão 2.0.0 adota uma arquitetura route-first e remove caminhos legados de transporte/configuração:

- **Registro explícito de endpoints**: use `Route::mcp('/mcp')` no Laravel e `McpRoute::register('/mcp')` no Lumen.
- **Apenas Streamable HTTP**: endpoints/adaptadores SSE legados foram removidos.
- **Sem bootstrap via config**: `config/mcp-server.php` e auto-registro de rotas não são mais usados.
- **Limpeza de métodos legados de transporte em tools**: `messageType()` foi removido e `isStreaming()` não é mais usado em runtime.
- **Descoberta de tools orientada por rotas**: `mcp:test-tool` agora lê tools de endpoints MCP registrados.

Veja o guia completo: [Guia de Migração v2.0.0](docs/migrations/v2.0.0-migration.md).

### Breaking Changes na v1.1.0

A versão 1.1.0 introduziu uma mudança significativa e breaking na `ToolInterface`. Se você está fazendo upgrade da v1.0.x, você **deve** atualizar suas implementações de tools para conformar com a nova interface.

**Principais Mudanças na `ToolInterface`:**

A `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` foi atualizada da seguinte forma:

1.  **Novo Método Adicionado:**

    - `messageType(): ProcessMessageType`
      - Este método é crucial para o novo suporte a HTTP stream e determina o tipo de mensagem sendo processada.

2.  **Renomeação de Métodos:**
    - `getName()` agora é `name()`
    - `getDescription()` agora é `description()`
    - `getInputSchema()` agora é `inputSchema()`
    - `getAnnotations()` agora é `annotations()`

**Como Atualizar Suas Tools:**

### Migração Automatizada de Tools para v1.1.0

Para auxiliar na transição para a nova `ToolInterface` introduzida na v1.1.0, incluímos um comando Artisan que pode ajudar a automatizar a refatoração de suas tools existentes:

```bash
php artisan mcp:migrate-tools {path?}
```

**O que ele faz:**

Este comando irá escanear arquivos PHP no diretório especificado (padrão `app/MCP/Tools/`) e tentará:

1.  **Identificar tools antigas:** Ele procura por classes implementando a `ToolInterface` com as assinaturas de método antigas.
2.  **Criar Backups:** Antes de fazer qualquer mudança, ele criará um backup do seu arquivo de tool original com extensão `.backup` (ex: `YourTool.php.backup`). Se um arquivo de backup já existir, o arquivo original será pulado para prevenir perda acidental de dados.
3.  **Refatorar a Tool:**
    - Renomear métodos:
      - `getName()` para `name()`
      - `getDescription()` para `description()`
      - `getInputSchema()` para `inputSchema()`
      - `getAnnotations()` para `annotations()`
    - Adicionar o novo método `messageType()`, que por padrão retornará `ProcessMessageType::SSE`.
    - Garantir que a declaração `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` esteja presente.

**Uso:**

Após atualizar o pacote `opgginc/laravel-mcp-server` para v1.1.0 ou posterior, se você tem tools existentes escritas para v1.0.x, é altamente recomendado executar este comando:

```bash
php artisan mcp:migrate-tools
```

Se suas tools estão localizadas em um diretório diferente de `app/MCP/Tools/`, você pode especificar o caminho:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

O comando mostrará seu progresso, indicando quais arquivos estão sendo processados, backupeados e migrados. Sempre revise as mudanças feitas pela ferramenta. Embora ela vise ser precisa, arquivos de tools complexos ou formatados de forma incomum podem requerer ajustes manuais.

Esta ferramenta deve facilitar significativamente o processo de migração e ajudá-lo a se adaptar à nova estrutura de interface rapidamente.

### Migração Manual

Se você preferir migrar suas tools manualmente, aqui está uma comparação para ajudá-lo a adaptar suas tools existentes:

**`ToolInterface` v1.0.x:**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

interface ToolInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getInputSchema(): array;
    public function getAnnotations(): array;
    public function execute(array $arguments): mixed;
}
```

**`ToolInterface` v1.1.0 (Nova):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // Novo método
    public function name(): string;                     // Renomeado
    public function description(): string;              // Renomeado
    public function inputSchema(): array;               // Renomeado
    public function annotations(): array;               // Renomeado
    public function execute(array $arguments): mixed;   // Sem mudança
}
```

**Exemplo de uma tool atualizada:**

Se sua tool v1.0.x era assim:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyOldTool implements ToolInterface
{
    public function getName(): string { return 'MyOldTool'; }
    public function getDescription(): string { return 'This is my old tool.'; }
    public function getInputSchema(): array { return []; }
    public function getAnnotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

Você precisa atualizá-la para v1.1.0 da seguinte forma:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // Importar o enum

class MyNewTool implements ToolInterface
{
    /**
     * @deprecated desde v1.3.0, use isStreaming() em vez disso. Será removido na v2.0.0
     */
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function isStreaming(): bool
    {
        return false; // A maioria das tools deve retornar false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Visão Geral do Laravel MCP Server

Laravel MCP Server é um pacote poderoso projetado para simplificar a implementação de servidores Model Context Protocol (MCP) em aplicações Laravel. **Diferente da maioria dos pacotes Laravel MCP que usam transporte Standard Input/Output (stdio)**, este pacote foca em transporte **Streamable HTTP** e ainda inclui um **provedor SSE legado** para compatibilidade com versões anteriores, fornecendo um método de integração seguro e controlado.

### Por que Streamable HTTP em vez de STDIO?

Embora stdio seja direto e amplamente usado em implementações MCP, ele tem implicações de segurança significativas para ambientes empresariais:

- **Risco de Segurança**: O transporte STDIO potencialmente expõe detalhes internos do sistema e especificações de API
- **Proteção de Dados**: Organizações precisam proteger endpoints de API proprietários e arquitetura interna do sistema
- **Controle**: Streamable HTTP oferece melhor controle sobre o canal de comunicação entre clientes LLM e sua aplicação

Ao implementar o servidor MCP com transporte Streamable HTTP, empresas podem:

- Expor apenas as tools e recursos necessários mantendo detalhes de API proprietários privados
- Manter controle sobre processos de autenticação e autorização

Principais benefícios:

- Implementação integrada e rápida de Streamable HTTP em projetos Laravel existentes
- Suporte para as versões mais recentes do Laravel e PHP
- Comunicação eficiente do servidor e processamento de dados em tempo real
- Segurança aprimorada para ambientes empresariais

## Principais Funcionalidades

- Suporte a comunicação em tempo real através de Streamable HTTP com integração SSE
- Implementação de tools e recursos compatíveis com especificações do Model Context Protocol
- Arquitetura baseada em adaptadores com padrão de mensagens Pub/Sub (começando com Redis, mais adaptadores planejados)
- Configuração simples de roteamento e middleware

### Provedores de Transporte

A opção de configuração `server_provider` controla qual transporte é usado. Provedores disponíveis são:

1. **streamable_http** – o padrão recomendado. Usa requisições HTTP padrão e evita problemas com plataformas que fecham conexões SSE após cerca de um minuto (ex: muitos ambientes serverless).
2. **sse** – um provedor legado mantido para compatibilidade com versões anteriores. Ele depende de conexões SSE de longa duração e pode não funcionar em plataformas com timeouts HTTP curtos.

O protocolo MCP também define um modo "Streamable HTTP SSE", mas este pacote não o implementa e não há planos para fazê-lo.

## Requisitos

- PHP >=8.2
- Laravel >=10.x

## Instalação

1. Instale o pacote via Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Publique o arquivo de configuração:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Uso Básico


### Criando e Adicionando Tools Customizadas

O pacote fornece comandos Artisan convenientes para gerar novas tools:

```bash
php artisan make:mcp-tool MyCustomTool
```

Este comando:

- Lida com vários formatos de entrada (espaços, hífens, maiúsculas e minúsculas mistas)
- Converte automaticamente o nome para formato de case apropriado
- Cria uma classe de tool adequadamente estruturada em `app/MCP/Tools`
- Oferece registrar automaticamente a tool na sua configuração

Você também pode criar e registrar tools manualmente em `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementação da tool
}
```

### Entendendo a Estrutura da Sua Tool (ToolInterface)

Quando você cria uma tool implementando `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, você precisará definir vários métodos. Aqui está uma explicação de cada método e seu propósito:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    /**
     * @deprecated desde v1.3.0, use isStreaming() em vez disso. Será removido na v2.0.0
     */
    public function messageType(): ProcessMessageType;

    // NOVO na v1.3.0: Determina se esta tool requer streaming (SSE) em vez de HTTP padrão.
    public function isStreaming(): bool;

    // O nome único e chamável da sua tool (ex: 'get-user-details').
    public function name(): string;

    // Uma descrição legível do que sua tool faz.
    public function description(): string;

    // Define os parâmetros de entrada esperados para sua tool usando uma estrutura similar ao JSON Schema.
    public function inputSchema(): array;

    // Fornece uma forma de adicionar metadados ou anotações arbitrárias à sua tool.
    public function annotations(): array;

    // A lógica central da sua tool. Recebe argumentos validados e retorna o resultado.
    public function execute(array $arguments): mixed;
}
```

Vamos nos aprofundar em alguns desses métodos:

**`messageType(): ProcessMessageType` (Depreciado na v1.3.0)**

⚠️ **Este método está depreciado desde a v1.3.0.** Use `isStreaming(): bool` em vez disso para melhor clareza.

Este método especifica o tipo de processamento de mensagem para sua tool. Ele retorna um valor enum `ProcessMessageType`. Os tipos disponíveis são:

- `ProcessMessageType::HTTP`: Para tools interagindo via requisição/resposta HTTP padrão. Mais comum para novas tools.
- `ProcessMessageType::SSE`: Para tools especificamente projetadas para trabalhar com Server-Sent Events.

Para a maioria das tools, especialmente aquelas projetadas para o provedor primário `streamable_http`, você retornará `ProcessMessageType::HTTP`.

**`isStreaming(): bool` (Novo na v1.3.0)**

Este é o novo método mais intuitivo para controlar padrões de comunicação:

- `return false`: Use requisição/resposta HTTP padrão (recomendado para a maioria das tools)
- `return true`: Use Server-Sent Events para streaming em tempo real

A maioria das tools deve retornar `false` a menos que você especificamente precise de capacidades de streaming em tempo real como:
- Atualizações de progresso em tempo real para operações de longa duração
- Feeds de dados ao vivo ou tools de monitoramento
- Tools interativas requerendo comunicação bidirecional

**`name(): string`**

Este é o identificador para sua tool. Deve ser único. Clientes usarão este nome para requisitar sua tool. Por exemplo: `get-weather`, `calculate-sum`.

**`description(): string`**

Uma descrição clara e concisa da funcionalidade da sua tool. Isso é usado na documentação, e UIs de clientes MCP (como o MCP Inspector) podem exibi-la para usuários.

**`inputSchema(): array`**

Este método é crucial para definir os parâmetros de entrada esperados da sua tool. Deve retornar um array que segue uma estrutura similar ao JSON Schema. Este schema é usado:

- Por clientes para entender quais dados enviar.
- Potencialmente pelo servidor ou cliente para validação de entrada.
- Por tools como o MCP Inspector para gerar formulários para teste.

**Exemplo `inputSchema()`:**

```php
public function inputSchema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'userId' => [
                'type' => 'integer',
                'description' => 'The unique identifier for the user.',
            ],
            'includeDetails' => [
                'type' => 'boolean',
                'description' => 'Whether to include extended details in the response.',
                'default' => false, // Você pode especificar valores padrão
            ],
        ],
        'required' => ['userId'], // Especifica quais propriedades são obrigatórias
    ];
}
```

No seu método `execute`, você pode então validar os argumentos recebidos. O exemplo `HelloWorldTool` usa `Illuminate\Support\Facades\Validator` para isso:

```php
// Dentro do seu método execute():
$validator = Validator::make($arguments, [
    'userId' => ['required', 'integer'],
    'includeDetails' => ['sometimes', 'boolean'],
]);

if ($validator->fails()) {
    throw new JsonRpcErrorException(
        message: $validator->errors()->toJson(),
        code: JsonRpcErrorCode::INVALID_REQUEST
    );
}
// Prosseguir com $arguments['userId'] e $arguments['includeDetails'] validados
```

**`annotations(): array`**

Este método fornece metadados sobre o comportamento e características da sua tool, seguindo a [especificação oficial de Tool Annotations do MCP](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Anotações ajudam clientes MCP a categorizar tools, tomar decisões informadas sobre aprovação de tools e fornecer interfaces de usuário apropriadas.

**Anotações MCP Padrão:**

O Model Context Protocol define várias anotações padrão que clientes entendem:

- **`title`** (string): Um título legível para a tool, exibido em UIs de cliente
- **`readOnlyHint`** (boolean): Indica se a tool apenas lê dados sem modificar o ambiente (padrão: false)
- **`destructiveHint`** (boolean): Sugere se a tool pode realizar operações destrutivas como deletar dados (padrão: true)
- **`idempotentHint`** (boolean): Indica se chamadas repetidas com os mesmos argumentos não têm efeito adicional (padrão: false)
- **`openWorldHint`** (boolean): Sinaliza se a tool interage com entidades externas além do ambiente local (padrão: true)

**Importante:** Estas são dicas, não garantias. Elas ajudam clientes a fornecer melhores experiências de usuário mas não devem ser usadas para decisões críticas de segurança.

**Exemplo com anotações MCP padrão:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // Tool apenas lê dados de usuário
        'destructiveHint' => false,    // Tool não deleta ou modifica dados
        'idempotentHint' => true,      // Seguro chamar múltiplas vezes
        'openWorldHint' => false,      // Tool apenas acessa banco de dados local
    ];
}
```

**Exemplos do mundo real por tipo de tool:**

```php
// Tool de consulta de banco de dados
public function annotations(): array
{
    return [
        'title' => 'Database Query Tool',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => false,
    ];
}

// Tool de deleção de post
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Pode deletar posts
        'idempotentHint' => false,     // Deletar duas vezes tem efeitos diferentes
        'openWorldHint' => false,
    ];
}

// Tool de integração de API
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Acessa API externa de clima
    ];
}
```

**Anotações customizadas** também podem ser adicionadas para suas necessidades específicas de aplicação:

```php
public function annotations(): array
{
    return [
        // Anotações MCP padrão
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // Anotações customizadas para sua aplicação
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Trabalhando com Resources

Resources expõem dados do seu servidor que podem ser lidos por clientes MCP. Eles são
**controlados pela aplicação**, significando que o cliente decide quando e como usá-los.
Crie resources concretos ou templates de URI em `app/MCP/Resources` e
`app/MCP/ResourceTemplates` usando os helpers Artisan:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

Registre as classes geradas em `config/mcp-server.php` sob os arrays `resources`
e `resource_templates`. Cada classe de resource estende a classe base
`Resource` e implementa um método `read()` que retorna conteúdo `text` ou
`blob`. Templates estendem `ResourceTemplate` e descrevem padrões de URI
dinâmicos que clientes podem usar. Um resource é identificado por uma URI como
`file:///logs/app.log` e pode opcionalmente definir metadados como `mimeType` ou
`size`.

**Resource Templates com Listagem Dinâmica**: Templates podem opcionalmente implementar um método `list()` para fornecer instâncias de resource concretas que correspondem ao padrão do template. Isso permite que clientes descubram resources disponíveis dinamicamente. O método `list()` permite que instâncias ResourceTemplate gerem uma lista de resources específicos que podem ser lidos através do método `read()` do template.

Liste resources disponíveis usando o endpoint `resources/list` e leia seus
conteúdos com `resources/read`. O endpoint `resources/list` retorna um array
de resources concretos, incluindo tanto resources estáticos quanto resources
gerados dinamicamente de templates que implementam o método `list()`:

```json
{
  "resources": [
    {
      "uri": "file:///logs/app.log",
      "name": "Application Log",
      "mimeType": "text/plain"
    },
    {
      "uri": "database://users/123",
      "name": "User: John Doe",
      "description": "Profile data for John Doe",
      "mimeType": "application/json"
    }
  ]
}
```

**Leitura Dinâmica de Resource**: Resource templates suportam padrões de template URI (RFC 6570) que permitem clientes construir identificadores de resource dinâmicos. Quando um cliente requisita uma URI de resource que corresponde a um padrão de template, o método `read()` do template é chamado com parâmetros extraídos para gerar o conteúdo do resource.

Exemplo de fluxo de trabalho:
1. Template define padrão: `"database://users/{userId}/profile"`
2. Cliente requisita: `"database://users/123/profile"`
3. Template extrai `{userId: "123"}` e chama método `read()`
4. Template retorna dados de perfil de usuário para ID de usuário 123

Você também pode listar templates separadamente usando o endpoint `resources/templates/list`:

```bash
# Listar apenas resource templates
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

Quando executando seu servidor Laravel MCP remotamente, o transporte HTTP funciona com
requisições JSON-RPC padrão. Aqui está um exemplo simples usando `curl` para listar e
ler resources:

```bash
# Listar resources
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# Ler um resource específico
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

O servidor responde com mensagens JSON transmitidas pela conexão HTTP, então
`curl --no-buffer` pode ser usado se você quiser ver saída incremental.

### Trabalhando com Prompts

Prompts fornecem snippets de texto reutilizáveis com suporte a argumentos que suas tools ou usuários podem requisitar.
Crie classes de prompt em `app/MCP/Prompts` usando:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

Registre-os em `config/mcp-server.php` sob `prompts`. Cada classe de prompt
estende a classe base `Prompt` e define:
- `name`: Identificador único (ex: "welcome-user")
- `description`: Descrição legível opcional  
- `arguments`: Array de definições de argumento com campos name, description e required
- `text`: O template de prompt com placeholders como `{username}`

Liste prompts via endpoint `prompts/list` e busque-os usando
`prompts/get` com argumentos:

```bash
# Buscar um prompt de boas-vindas com argumentos
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### MCP Prompts

Ao criar prompts que referenciam suas tools ou resources, consulte as [diretrizes oficiais de prompt](https://modelcontextprotocol.io/docs/concepts/prompts). Prompts são templates reutilizáveis que podem aceitar argumentos, incluir contexto de resource e até mesmo descrever fluxos de trabalho multi-etapas.

**Estrutura de prompt**

```json
{
  "name": "string",
  "description": "string",
  "arguments": [
    {
      "name": "string",
      "description": "string",
      "required": true
    }
  ]
}
```

Clientes descobrem prompts via `prompts/list` e requisitam específicos com `prompts/get`:

```json
{
  "method": "prompts/get",
  "params": {
    "name": "analyze-code",
    "arguments": {
      "language": "php"
    }
  }
}
```

**Exemplo de Classe Prompt**

```php
use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';
    
    public ?string $description = 'A customizable welcome message for users';
    
    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'The name of the user to welcome',
            'required' => true,
        ],
        [
            'name' => 'role',
            'description' => 'The role of the user (optional)',
            'required' => false,
        ],
    ];
    
    public string $text = 'Welcome, {username}! You are logged in as {role}.';
}
```

Prompts podem incorporar resources e retornar sequências de mensagens para guiar um LLM. Veja a documentação oficial para exemplos avançados e melhores práticas.

### Trabalhando com Notificações

Notificações são mensagens fire-and-forget de clientes MCP que sempre retornam HTTP 202 Accepted sem corpo de resposta. São perfeitas para logging, rastreamento de progresso, tratamento de eventos e acionamento de processos em segundo plano sem bloquear o cliente.

#### Criando Manipuladores de Notificação

**Uso básico do comando:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**Recursos avançados do comando:**

```bash
# Modo interativo - solicita método se não especificado
php artisan make:mcp-notification MyHandler

# Tratamento automático de prefixo de método
php artisan make:mcp-notification StatusHandler --method=status  # torna-se notifications/status

# Normalização de nome de classe 
php artisan make:mcp-notification "user activity"  # torna-se UserActivityHandler
```

O comando fornece:
- **Solicitação interativa de método** quando `--method` não é especificado
- **Guia de registro automático** com código pronto para copiar e colar
- **Exemplos de teste integrados** com comandos curl 
- **Instruções de uso abrangentes** e casos de uso comuns

#### Arquitetura do Manipulador de Notificação

Cada manipulador de notificação deve implementar a classe abstrata `NotificationHandler`:

```php
abstract class NotificationHandler
{
    // Obrigatório: Tipo de mensagem (geralmente ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // Obrigatório: O método de notificação a ser tratado  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // Obrigatório: Executar a lógica de notificação
    abstract public function execute(?array $params = null): void;
}
```

**Componentes arquiteturais principais:**

- **`MESSAGE_TYPE`**: Geralmente `ProcessMessageType::HTTP` para notificações padrão
- **`HANDLE_METHOD`**: O método JSON-RPC que este manipulador processa (deve começar com `notifications/`)
- **`execute()`**: Contém sua lógica de notificação - retorna void (nenhuma resposta enviada)
- **Validação do construtor**: Valida automaticamente que as constantes obrigatórias estão definidas

#### Manipuladores de Notificação Integrados

O pacote inclui quatro manipuladores pré-construídos para cenários MCP comuns:

**1. InitializedHandler (`notifications/initialized`)**
- **Propósito**: Processa confirmações de inicialização do cliente após handshake bem-sucedido
- **Parâmetros**: Informações e capacidades do cliente
- **Uso**: Rastreamento de sessão, logging de cliente, eventos de inicialização

**2. ProgressHandler (`notifications/progress`)**
- **Propósito**: Trata atualizações de progresso para operações de longa duração
- **Parâmetros**: 
  - `progressToken` (string): Identificador único para a operação
  - `progress` (number): Valor de progresso atual
  - `total` (number, opcional): Valor total de progresso para cálculo de porcentagem
- **Uso**: Rastreamento de progresso em tempo real, monitoramento de uploads, conclusão de tarefas

**3. CancelledHandler (`notifications/cancelled`)**
- **Propósito**: Processa notificações de cancelamento de solicitação
- **Parâmetros**:
  - `requestId` (string): ID da solicitação a ser cancelada
  - `reason` (string, opcional): Motivo do cancelamento
- **Uso**: Terminação de jobs em segundo plano, limpeza de recursos, aborto de operações

**4. MessageHandler (`notifications/message`)**
- **Propósito**: Trata mensagens gerais de logging e comunicação
- **Parâmetros**:
  - `level` (string): Nível de log (info, warning, error, debug)
  - `message` (string): O conteúdo da mensagem
  - `logger` (string, opcional): Nome do logger
- **Uso**: Logging do lado do cliente, depuração, comunicação geral

#### Exemplos de Manipuladores para Cenários Comuns

```php
// Rastreamento de progresso de upload de arquivo
class UploadProgressHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/upload_progress';

    public function execute(?array $params = null): void
    {
        $token = $params['progressToken'] ?? null;
        $progress = $params['progress'] ?? 0;
        $total = $params['total'] ?? 100;
        
        if ($token) {
            Cache::put("upload_progress_{$token}", [
                'progress' => $progress,
                'total' => $total,
                'percentage' => $total ? round(($progress / $total) * 100, 2) : 0,
                'updated_at' => now()
            ], 3600);
            
            // Transmitir atualização em tempo real
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// Atividade do usuário e logging de auditoria
class UserActivityHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/user_activity';

    public function execute(?array $params = null): void
    {
        UserActivity::create([
            'user_id' => $params['userId'] ?? null,
            'action' => $params['action'] ?? 'unknown',
            'resource' => $params['resource'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $params['metadata'] ?? [],
            'created_at' => now()
        ]);
        
        // Acionar alertas de segurança para ações sensíveis
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// Acionamento de tarefas em segundo plano
class TaskTriggerHandler extends NotificationHandler
{
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    protected const HANDLE_METHOD = 'notifications/trigger_task';

    public function execute(?array $params = null): void
    {
        $taskType = $params['taskType'] ?? null;
        $taskData = $params['data'] ?? [];
        
        match ($taskType) {
            'send_email' => SendEmailJob::dispatch($taskData),
            'generate_report' => GenerateReportJob::dispatch($taskData),
            'sync_data' => DataSyncJob::dispatch($taskData),
            'cleanup' => CleanupJob::dispatch($taskData),
            default => Log::warning("Unknown task type: {$taskType}")
        };
    }
}
```

#### Registrando Manipuladores de Notificação

**Em seu provedor de serviços:**

```php
// No AppServiceProvider ou provedor de serviços MCP dedicado
public function boot()
{
    $server = app(MCPServer::class);
    
    // Registrar manipuladores integrados (opcional - são registrados por padrão)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // Registrar manipuladores personalizados
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### Testando Notificações

**Usando curl para testar manipuladores de notificação:**

```bash
# Testar notificação de progresso
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "notifications/progress",
    "params": {
      "progressToken": "upload_123",
      "progress": 75,
      "total": 100
    }
  }'
# Esperado: HTTP 202 com corpo vazio

# Testar notificação de atividade do usuário  
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", 
    "method": "notifications/user_activity",
    "params": {
      "userId": 123,
      "action": "file_download",
      "resource": "document.pdf"
    }
  }'
# Esperado: HTTP 202 com corpo vazio

# Testar notificação de cancelamento
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0",
    "method": "notifications/cancelled", 
    "params": {
      "requestId": "req_abc123",
      "reason": "User requested cancellation"
    }
  }'
# Esperado: HTTP 202 com corpo vazio
```

**Notas importantes sobre testes:**
- Notificações retornam **HTTP 202** (nunca 200)
- Corpo da resposta está **sempre vazio**
- Nenhuma mensagem de resposta JSON-RPC é enviada
- Verificar logs do servidor para confirmar processamento de notificações

#### Tratamento de Erros e Validação

**Padrões de validação comuns:**

```php
public function execute(?array $params = null): void
{
    // Validar parâmetros obrigatórios
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // Não lance exceção - notificações devem ser tolerantes a falhas
    }
    
    // Validar tipos de parâmetros
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // Extração segura de parâmetros com valores padrão
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // Processar notificação...
}
```

**Melhores práticas de tratamento de erros:**
- **Registrar erros** em vez de lançar exceções
- **Usar programação defensiva** com verificações null e valores padrão
- **Falha elegante** - não quebrar o fluxo de trabalho do cliente
- **Validar entradas** mas continuar processamento quando possível
- **Monitorar notificações** através de logging e métricas

### Testando MCP Tools

O pacote inclui um comando especial para testar suas MCP tools sem precisar de um cliente MCP real:

```bash
# Testar uma tool específica interativamente
php artisan mcp:test-tool MyCustomTool

# Listar todas as tools disponíveis
php artisan mcp:test-tool --list

# Testar com entrada JSON específica
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

Isso ajuda você a desenvolver e debugar tools rapidamente:

- Mostrando o schema de entrada da tool e validando entradas
- Executando a tool com sua entrada fornecida
- Exibindo resultados formatados ou informações detalhadas de erro
- Suportando tipos de entrada complexos incluindo objetos e arrays

### Visualizando MCP Tools com Inspector

Você também pode usar o Model Context Protocol Inspector para visualizar e testar suas MCP tools:

```bash
# Executar o MCP Inspector sem instalação
npx @modelcontextprotocol/inspector node build/index.js
```

Isso normalmente abrirá uma interface web em `localhost:6274`. Para testar seu servidor MCP:

1. **Aviso**: `php artisan serve` NÃO PODE ser usado com este pacote porque não consegue lidar com múltiplas conexões PHP simultaneamente. Como MCP SSE requer processamento de múltiplas conexões concorrentemente, você deve usar uma dessas alternativas:

   - **Laravel Octane** (Opção mais fácil):

     ```bash
     # Instalar e configurar Laravel Octane com FrankenPHP (recomendado)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Iniciar o servidor Octane
     php artisan octane:start
     ```

     > **Importante**: Ao instalar Laravel Octane, certifique-se de usar FrankenPHP como servidor. O pacote pode não funcionar adequadamente com RoadRunner devido a problemas de compatibilidade com conexões SSE. Se você pode ajudar a corrigir este problema de compatibilidade com RoadRunner, por favor submeta um Pull Request - sua contribuição seria muito apreciada!

     Para detalhes, veja a [documentação do Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opções de nível de produção**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Setup Docker customizado

   * Qualquer servidor web que suporte adequadamente streaming SSE (necessário apenas para o provedor SSE legado)

2. Na interface do Inspector, digite a URL do endpoint MCP do seu servidor Laravel (ex: `http://localhost:8000/mcp`). Se você está usando o provedor SSE legado, use a URL SSE em vez disso (`http://localhost:8000/mcp/sse`).
3. Conecte e explore tools disponíveis visualmente

O endpoint MCP segue o padrão: `http://[seu-servidor-laravel]/[default_path]` onde `default_path` é definido no seu arquivo `config/mcp-server.php`.

## Funcionalidades Avançadas

### Arquitetura Pub/Sub com Adaptadores SSE (provedor legado)

O pacote implementa um padrão de mensagens publish/subscribe (pub/sub) através de seu sistema de adaptadores:

1. **Publisher (Servidor)**: Quando clientes enviam requisições para o endpoint `/message`, o servidor processa essas requisições e publica respostas através do adaptador configurado.

2. **Message Broker (Adaptador)**: O adaptador (ex: Redis) mantém filas de mensagem para cada cliente, identificadas por IDs de cliente únicos. Isso fornece uma camada de comunicação assíncrona confiável.

3. **Subscriber (conexão SSE)**: Conexões SSE de longa duração se inscrevem em mensagens para seus respectivos clientes e as entregam em tempo real. Isso se aplica apenas quando usando o provedor SSE legado.

Esta arquitetura permite:

- Comunicação em tempo real escalável
- Entrega confiável de mensagens mesmo durante desconexões temporárias
- Manuseio eficiente de múltiplas conexões de cliente concorrentes
- Potencial para deployments de servidor distribuído

### Configuração do Adaptador Redis

O adaptador Redis padrão pode ser configurado da seguinte forma:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefixo para chaves Redis
        'connection' => 'default', // Conexão Redis de database.php
        'ttl' => 100,              // TTL de mensagem em segundos
    ],
],
```


## Tradução README.md

Para traduzir este README para outros idiomas usando Claude API (Processamento paralelo):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Você também pode traduzir idiomas específicos:

```bash
python scripts/translate_readme.py es ko
```

## Notas de Migração para v2.0.0

A versão 2.0.0 já está disponível. Se você está migrando da v1.x, aplique as mudanças abaixo.

### O que mudou na v2.0.0

- `messageType(): ProcessMessageType` foi removido.
- `isStreaming(): bool` não é mais usado em runtime (limpeza opcional).
- `ProcessMessageType::SSE` foi removido.
- Streamable HTTP é o único transporte suportado (`/sse` e `/message` foram removidos).
- Chaves de configuração MCP (`server_provider`, `sse_adapter`, `adapters`, `enabled`) foram removidas.

### Como migrar

- Registre endpoints MCP diretamente nas rotas com `Route::mcp(...)` (Laravel) ou `McpRoute::register(...)` (Lumen).
- Mova server info/tools/resources/templates/prompts do config para a cadeia do route builder.
- Execute `php artisan mcp:migrate-tools` para limpar assinaturas legadas de tools.
- Atualize os endpoints dos clientes MCP para seu caminho real de rota (por exemplo, `/mcp`).
- Guia completo: [Guia de Migração v2.0.0](docs/migrations/v2.0.0-migration.md).

### Verificação pós-migração

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
vendor/bin/pest
vendor/bin/phpstan analyse
```

## Licença

Este projeto é distribuído sob a licença MIT.