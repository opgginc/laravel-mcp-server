<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Um pacote Laravel poderoso para construir um Servidor de Protocolo de Contexto de Modelo de forma integrada
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

## ⚠️ Mudanças Incompatíveis na v1.1.0

A versão 1.1.0 introduziu uma mudança significativa e incompatível na `ToolInterface`. Se você está atualizando da v1.0.x, você **deve** atualizar suas implementações de ferramentas para se adequar à nova interface.

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

**Como Atualizar Suas Ferramentas:**

### Migração Automatizada de Ferramentas para v1.1.0

Para auxiliar na transição para a nova `ToolInterface` introduzida na v1.1.0, incluímos um comando Artisan que pode ajudar a automatizar a refatoração de suas ferramentas existentes:

```bash
php artisan mcp:migrate-tools {path?}
```

**O que ele faz:**

Este comando irá escanear arquivos PHP no diretório especificado (padrão `app/MCP/Tools/`) e tentará:

1.  **Identificar ferramentas antigas:** Ele procura por classes implementando a `ToolInterface` com as assinaturas de método antigas.
2.  **Criar Backups:** Antes de fazer qualquer alteração, ele criará um backup do seu arquivo de ferramenta original com extensão `.backup` (ex: `YourTool.php.backup`). Se um arquivo de backup já existir, o arquivo original será pulado para prevenir perda acidental de dados.
3.  **Refatorar a Ferramenta:**
    - Renomear métodos:
      - `getName()` para `name()`
      - `getDescription()` para `description()`
      - `getInputSchema()` para `inputSchema()`
      - `getAnnotations()` para `annotations()`
    - Adicionar o novo método `messageType()`, que por padrão retornará `ProcessMessageType::SSE`.
    - Garantir que a declaração `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` esteja presente.

**Uso:**

Após atualizar o pacote `opgginc/laravel-mcp-server` para v1.1.0 ou posterior, se você tem ferramentas existentes escritas para v1.0.x, é altamente recomendado executar este comando:

```bash
php artisan mcp:migrate-tools
```

Se suas ferramentas estão localizadas em um diretório diferente de `app/MCP/Tools/`, você pode especificar o caminho:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

O comando mostrará seu progresso, indicando quais arquivos estão sendo processados, backupeados e migrados. Sempre revise as alterações feitas pela ferramenta. Embora ela tente ser precisa, arquivos de ferramentas complexos ou formatados de forma incomum podem requerer ajustes manuais.

Esta ferramenta deve facilitar significativamente o processo de migração e ajudá-lo a se adaptar à nova estrutura de interface rapidamente.

### Migração Manual

Se você preferir migrar suas ferramentas manualmente, aqui está uma comparação para ajudá-lo a adaptar suas ferramentas existentes:

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

**Exemplo de uma ferramenta atualizada:**

Se sua ferramenta v1.0.x era assim:

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
    // Adicionar o novo método messageType()
    public function messageType(): ProcessMessageType
    {
        // Retornar o tipo de mensagem apropriado, ex: para uma ferramenta padrão
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // Renomeado
    public function description(): string { return 'This is my new tool.'; } // Renomeado
    public function inputSchema(): array { return []; } // Renomeado
    public function annotations(): array { return []; } // Renomeado
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Visão Geral do Laravel MCP Server

Laravel MCP Server é um pacote poderoso projetado para simplificar a implementação de servidores Model Context Protocol (MCP) em aplicações Laravel. **Diferentemente da maioria dos pacotes Laravel MCP que usam transporte Standard Input/Output (stdio)**, este pacote foca em transporte **HTTP Streamable** e ainda inclui um **provedor SSE legado** para compatibilidade com versões anteriores, fornecendo um método de integração seguro e controlado.

### Por que HTTP Streamable ao invés de STDIO?

Embora stdio seja direto e amplamente usado em implementações MCP, ele tem implicações de segurança significativas para ambientes corporativos:

- **Risco de Segurança**: O transporte STDIO potencialmente expõe detalhes internos do sistema e especificações de API
- **Proteção de Dados**: Organizações precisam proteger endpoints de API proprietários e arquitetura interna do sistema
- **Controle**: HTTP Streamable oferece melhor controle sobre o canal de comunicação entre clientes LLM e sua aplicação

Ao implementar o servidor MCP com transporte HTTP Streamable, empresas podem:

- Expor apenas as ferramentas e recursos necessários mantendo detalhes de API proprietários privados
- Manter controle sobre processos de autenticação e autorização

Principais benefícios:

- Implementação integrada e rápida de HTTP Streamable em projetos Laravel existentes
- Suporte para as versões mais recentes do Laravel e PHP
- Comunicação eficiente do servidor e processamento de dados em tempo real
- Segurança aprimorada para ambientes corporativos

## Principais Recursos

- Suporte a comunicação em tempo real através de HTTP Streamable com integração SSE
- Implementação de ferramentas e recursos compatíveis com especificações do Model Context Protocol
- Arquitetura de design baseada em adaptadores com padrão de mensagens Pub/Sub (começando com Redis, mais adaptadores planejados)
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

### Restrição de Domínio

Você pode restringir as rotas do servidor MCP a domínio(s) específico(s) para melhor segurança e organização:

```php
// config/mcp-server.php

// Permitir acesso de todos os domínios (padrão)
'domain' => null,

// Restringir a um único domínio
'domain' => 'api.example.com',

// Restringir a múltiplos domínios
'domain' => ['api.example.com', 'admin.example.com'],
```

**Quando usar restrição de domínio:**
- Executando múltiplas aplicações em diferentes subdomínios
- Separando endpoints de API da sua aplicação principal
- Implementando arquiteturas multi-tenant onde cada tenant tem seu próprio subdomínio
- Fornecendo os mesmos serviços MCP através de múltiplos domínios

**Cenários de exemplo:**

```php
// Subdomínio único de API
'domain' => 'api.op.gg',

// Múltiplos subdomínios para diferentes ambientes
'domain' => ['api.op.gg', 'staging-api.op.gg'],

// Arquitetura multi-tenant
'domain' => ['tenant1.op.gg', 'tenant2.op.gg', 'tenant3.op.gg'],

// Diferentes serviços em diferentes domínios
'domain' => ['api.op.gg', 'api.kargn.as'],
```

> **Nota:** Ao usar múltiplos domínios, o pacote registra automaticamente rotas separadas para cada domínio para garantir roteamento adequado através de todos os domínios especificados.

### Criando e Adicionando Ferramentas Personalizadas

O pacote fornece comandos Artisan convenientes para gerar novas ferramentas:

```bash
php artisan make:mcp-tool MyCustomTool
```

Este comando:

- Lida com vários formatos de entrada (espaços, hífens, maiúsculas e minúsculas mistas)
- Converte automaticamente o nome para formato de caso adequado
- Cria uma classe de ferramenta adequadamente estruturada em `app/MCP/Tools`
- Oferece registrar automaticamente a ferramenta em sua configuração

Você também pode criar e registrar ferramentas manualmente em `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementação da ferramenta
}
```

### Entendendo a Estrutura da Sua Ferramenta (ToolInterface)

Quando você cria uma ferramenta implementando `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, você precisará definir vários métodos. Aqui está um detalhamento de cada método e seu propósito:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    // Determina como as mensagens da ferramenta são processadas, frequentemente relacionado ao transporte.
    public function messageType(): ProcessMessageType;

    // O nome único e chamável da sua ferramenta (ex: 'get-user-details').
    public function name(): string;

    // Uma descrição legível do que sua ferramenta faz.
    public function description(): string;

    // Define os parâmetros de entrada esperados para sua ferramenta usando uma estrutura similar ao JSON Schema.
    public function inputSchema(): array;

    // Fornece uma maneira de adicionar metadados ou anotações arbitrárias à sua ferramenta.
    public function annotations(): array;

    // A lógica central da sua ferramenta. Recebe argumentos validados e retorna o resultado.
    public function execute(array $arguments): mixed;
}
```

Vamos nos aprofundar em alguns desses métodos:

**`messageType(): ProcessMessageType`**

Este método especifica o tipo de processamento de mensagem para sua ferramenta. Ele retorna um valor enum `ProcessMessageType`. Os tipos disponíveis são:

- `ProcessMessageType::HTTP`: Para ferramentas interagindo via requisição/resposta HTTP padrão. Mais comum para novas ferramentas.
- `ProcessMessageType::SSE`: Para ferramentas especificamente projetadas para trabalhar com Server-Sent Events.

Para a maioria das ferramentas, especialmente aquelas projetadas para o provedor primário `streamable_http`, você retornará `ProcessMessageType::HTTP`.

**`name(): string`**

Este é o identificador para sua ferramenta. Deve ser único. Clientes usarão este nome para solicitar sua ferramenta. Por exemplo: `get-weather`, `calculate-sum`.

**`description(): string`**

Uma descrição clara e concisa da funcionalidade da sua ferramenta. Isso é usado na documentação, e UIs de cliente MCP (como o MCP Inspector) podem exibi-la aos usuários.

**`inputSchema(): array`**

Este método é crucial para definir os parâmetros de entrada esperados da sua ferramenta. Deve retornar um array que segue uma estrutura similar ao JSON Schema. Este schema é usado:

- Por clientes para entender quais dados enviar.
- Potencialmente pelo servidor ou cliente para validação de entrada.
- Por ferramentas como o MCP Inspector para gerar formulários para teste.

**Exemplo de `inputSchema()`:**

```php
public function inputSchema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'userId' => [
                'type' => 'integer',
                'description' => 'O identificador único para o usuário.',
            ],
            'includeDetails' => [
                'type' => 'boolean',
                'description' => 'Se deve incluir detalhes estendidos na resposta.',
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

Este método fornece metadados sobre o comportamento e características da sua ferramenta, seguindo a [especificação oficial de Anotações de Ferramenta MCP](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Anotações ajudam clientes MCP a categorizar ferramentas, tomar decisões informadas sobre aprovação de ferramentas e fornecer interfaces de usuário apropriadas.

**Anotações MCP Padrão:**

O Model Context Protocol define várias anotações padrão que clientes entendem:

- **`title`** (string): Um título legível para a ferramenta, exibido em UIs de cliente
- **`readOnlyHint`** (boolean): Indica se a ferramenta apenas lê dados sem modificar o ambiente (padrão: false)
- **`destructiveHint`** (boolean): Sugere se a ferramenta pode realizar operações destrutivas como deletar dados (padrão: true)
- **`idempotentHint`** (boolean): Indica se chamadas repetidas com os mesmos argumentos não têm efeito adicional (padrão: false)
- **`openWorldHint`** (boolean): Sinaliza se a ferramenta interage com entidades externas além do ambiente local (padrão: true)

**Importante:** Estas são dicas, não garantias. Elas ajudam clientes a fornecer melhores experiências de usuário, mas não devem ser usadas para decisões críticas de segurança.

**Exemplo com anotações MCP padrão:**

```php
public function annotations(): array
{
    return [
        'title' => 'Buscador de Perfil de Usuário',
        'readOnlyHint' => true,        // Ferramenta apenas lê dados de usuário
        'destructiveHint' => false,    // Ferramenta não deleta ou modifica dados
        'idempotentHint' => true,      // Seguro chamar múltiplas vezes
        'openWorldHint' => false,      // Ferramenta apenas acessa banco de dados local
    ];
}
```

**Exemplos do mundo real por tipo de ferramenta:**

```php
// Ferramenta de consulta de banco de dados
public function annotations(): array
{
    return [
        'title' => 'Ferramenta de Consulta de Banco de Dados',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => false,
    ];
}

// Ferramenta de deleção de post
public function annotations(): array
{
    return [
        'title' => 'Ferramenta de Deleção de Post do Blog',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Pode deletar posts
        'idempotentHint' => false,     // Deletar duas vezes tem efeitos diferentes
        'openWorldHint' => false,
    ];
}

// Ferramenta de integração de API
public function annotations(): array
{
    return [
        'title' => 'API do Clima',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Acessa API externa de clima
    ];
}
```

**Anotações personalizadas** também podem ser adicionadas para suas necessidades específicas de aplicação:

```php
public function annotations(): array
{
    return [
        // Anotações MCP padrão
        'title' => 'Ferramenta Personalizada',
        'readOnlyHint' => true,

        // Anotações personalizadas para sua aplicação
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Equipe de Dados',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Testando Ferramentas MCP

O pacote inclui um comando especial para testar suas ferramentas MCP sem precisar de um cliente MCP real:

```bash
# Testar uma ferramenta específica interativamente
php artisan mcp:test-tool MyCustomTool

# Listar todas as ferramentas disponíveis
php artisan mcp:test-tool --list

# Testar com entrada JSON específica
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

Isso ajuda você a desenvolver e debugar ferramentas rapidamente:

- Mostrando o schema de entrada da ferramenta e validando entradas
- Executando a ferramenta com sua entrada fornecida
- Exibindo resultados formatados ou informações detalhadas de erro
- Suportando tipos de entrada complexos incluindo objetos e arrays

### Visualizando Ferramentas MCP com Inspector

Você também pode usar o Model Context Protocol Inspector para visualizar e testar suas ferramentas MCP:

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

     > **Importante**: Ao instalar Laravel Octane, certifique-se de usar FrankenPHP como servidor. O pacote pode não funcionar adequadamente com RoadRunner devido a problemas de compatibilidade com conexões SSE. Se você pode ajudar a corrigir este problema de compatibilidade com RoadRunner, por favor envie um Pull Request - sua contribuição seria muito apreciada!

     Para detalhes, veja a [documentação do Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opções de nível de produção**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Configuração Docker personalizada

   * Qualquer servidor web que suporte adequadamente streaming SSE (necessário apenas para o provedor SSE legado)

2. Na interface do Inspector, digite a URL do endpoint MCP do seu servidor Laravel (ex: `http://localhost:8000/mcp`). Se você está usando o provedor SSE legado, use a URL SSE em vez disso (`http://localhost:8000/mcp/sse`).
3. Conecte e explore ferramentas disponíveis visualmente

O endpoint MCP segue o padrão: `http://[seu-servidor-laravel]/[default_path]` onde `default_path` é definido no seu arquivo `config/mcp-server.php`.

## Recursos Avançados

### Arquitetura Pub/Sub com Adaptadores SSE (provedor legado)

O pacote implementa um padrão de mensagens publish/subscribe (pub/sub) através de seu sistema de adaptadores:

1. **Publisher (Servidor)**: Quando clientes enviam requisições para o endpoint `/message`, o servidor processa essas requisições e publica respostas através do adaptador configurado.

2. **Message Broker (Adaptador)**: O adaptador (ex: Redis) mantém filas de mensagens para cada cliente, identificadas por IDs únicos de cliente. Isso fornece uma camada de comunicação assíncrona confiável.

3. **Subscriber (conexão SSE)**: Conexões SSE de longa duração se inscrevem em mensagens para seus respectivos clientes e as entregam em tempo real. Isso se aplica apenas ao usar o provedor SSE legado.

Esta arquitetura permite:

- Comunicação escalável em tempo real
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
        'ttl' => 100,              // TTL da mensagem em segundos
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

## Licença

Este projeto é distribuído sob a licença MIT.