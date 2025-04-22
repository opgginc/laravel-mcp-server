<h1 align="center">Laravel MCP Server pela OP.GG</h1>

<p align="center">
  Um pacote Laravel poderoso para construir um Servidor de Protocolo de Contexto de Modelo de forma simples
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Status de Build"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total de Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Última Versão Estável"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="Licença"></a>
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
  <a href="README.pl.md">Polski</a>
</p>

## Visão Geral

O Laravel MCP Server é um pacote poderoso feito pra facilitar a implementação de servidores MCP (Model Context Protocol) em apps Laravel. **Ao contrário da maioria dos pacotes MCP pro Laravel que usam stdio (entrada/saída padrão)**, este pacote **usa SSE (Server-Sent Events)**, proporcionando uma integração mais segura e com melhor controle.

### Por que SSE em vez de STDIO?

Apesar do stdio ser simples e muito usado nas implementações de MCP, ele traz problemas sérios de segurança em ambientes corporativos:

- **Risco de Segurança**: O STDIO pode expor detalhes internos do sistema e especificações de API
- **Proteção de Dados**: Empresas precisam proteger seus endpoints de API e a arquitetura interna
- **Controle**: O SSE dá um controle muito melhor sobre a comunicação entre clientes LLM e sua app

Usando SSE pro servidor MCP, as empresas conseguem:

- Expor só as ferramentas e recursos necessários, mantendo os detalhes da API privados
- Controlar melhor os processos de autenticação e autorização

Principais vantagens:

- Implementação rápida e fácil de SSE em projetos Laravel já existentes
- Suporte às versões mais recentes do Laravel e PHP
- Comunicação eficiente e processamento de dados em tempo real
- Segurança reforçada pra ambientes corporativos

## Recursos Principais

- Suporte à comunicação em tempo real através da integração de Eventos Enviados pelo Servidor (SSE)
- Implementação de ferramentas e recursos compatíveis com as especificações do Protocolo de Contexto de Modelo
- Arquitetura de design baseada em adaptadores com padrão de mensagens Pub/Sub (começando com Redis, mais adaptadores planejados)
- Configuração simples de rotas e middlewares

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

### Criando e Adicionando Ferramentas Personalizadas

O pacote fornece comandos Artisan convenientes para gerar novas ferramentas:

```bash
php artisan make:mcp-tool MyCustomTool
```

Esse comando:

- Aceita vários formatos de entrada (com espaços, hífens, caixa mista)
- Converte o nome pro formato de case correto automaticamente
- Cria uma classe bem estruturada em `app/MCP/Tools`
- Oferece pra registrar a ferramenta na sua configuração automaticamente

Você também pode criar e registrar ferramentas manualmente no `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementação da ferramenta
}
```

### Testando Ferramentas MCP

O pacote inclui um comando especial para testar suas ferramentas MCP sem precisar de um cliente MCP real:

```bash
# Teste uma ferramenta específica interativamente
php artisan mcp:test-tool MyCustomTool

# Liste todas as ferramentas disponíveis
php artisan mcp:test-tool --list

# Teste com entrada JSON específica
php artisan mcp:test-tool MyCustomTool --input='{"param":"valor"}'
```

Isso ajuda você a desenvolver e depurar ferramentas rapidamente:

- Mostrando o esquema de entrada da ferramenta e validando entradas
- Executando a ferramenta com sua entrada fornecida
- Exibindo resultados formatados ou informações detalhadas de erro
- Suportando tipos complexos de entrada, incluindo objetos e arrays

### Visualização de Ferramentas MCP com o Inspector

Dá pra usar o Inspector do MCP pra visualizar e testar suas ferramentas:

```bash
# Roda o MCP Inspector sem precisar instalar
npx @modelcontextprotocol/inspector node build/index.js
```

Isso vai abrir uma interface web em `localhost:6274`. Pra testar seu servidor MCP:

1. **ATENÇÃO**: NÃO É POSSÍVEL usar `php artisan serve` com este pacote porque ele não consegue processar múltiplas conexões PHP simultaneamente. Como o MCP SSE precisa processar várias conexões ao mesmo tempo, você deve usar uma destas alternativas:

   * **Laravel Octane** (opção mais fácil):
      ```bash
      # Instalar e configurar Laravel Octane com FrankenPHP (recomendado)
      composer require laravel/octane
      php artisan octane:install --server=frankenphp
      
      # Iniciar o servidor Octane
      php artisan octane:start
      ```
      
      > **Importante**: Ao instalar o Laravel Octane, certifique-se de usar o FrankenPHP como servidor. O pacote pode não funcionar corretamente com o RoadRunner devido a problemas de compatibilidade com conexões SSE. Se você puder ajudar a resolver este problema de compatibilidade com o RoadRunner, por favor envie um Pull Request - sua contribuição seria muito apreciada!
      
      Para detalhes, consulte a [documentação do Laravel Octane](https://laravel.com/docs/12.x/octane)
     
   * **Opções para produção**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Configuração Docker personalizada
     - Qualquer servidor web que suporte streaming SSE corretamente

2. No Inspector, coloque a URL SSE do seu servidor (tipo `http://localhost:8000/mcp/sse`)
3. Conecte e explore as ferramentas visualmente

A URL SSE segue o formato: `http://[seu-servidor]/[default_path]/sse` onde o `default_path` tá configurado no seu `config/mcp-server.php`.

## Recursos Avançados

### Arquitetura Pub/Sub com Adaptadores SSE

O pacote implementa um padrão de mensagens publicar/assinar (pub/sub) através do seu sistema de adaptadores:

1. **Publicador (Servidor)**: Quando clientes enviam requisições para o endpoint `/message`, o servidor processa essas requisições e publica respostas através do adaptador configurado.

2. **Intermediário de Mensagens (Adaptador)**: O adaptador (ex: Redis) mantém filas de mensagens para cada cliente, identificados por IDs de cliente únicos. Isso fornece uma camada de comunicação assíncrona confiável.

3. **Assinante (Conexão SSE)**: Conexões SSE de longa duração assinam mensagens para seus respectivos clientes e as entregam em tempo real.

Esta arquitetura permite:

- Comunicação em tempo real escalável
- Entrega confiável de mensagens mesmo durante desconexões temporárias
- Tratamento eficiente de múltiplas conexões simultâneas de clientes
- Potencial para implantações de servidor distribuídas

### Configuração do Adaptador Redis

O adaptador Redis padrão pode ser configurado da seguinte forma:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefixo para chaves Redis
        'connection' => 'default', // Conexão Redis do database.php
        'ttl' => 100,              // TTL da mensagem em segundos
    ],
],
```

## Variáveis de Ambiente

O pacote suporta as seguintes variáveis de ambiente para permitir configuração sem modificar os arquivos de configuração:

| Variável | Descrição | Padrão |
|----------|-------------|--------|
| `MCP_SERVER_ENABLED` | Habilitar ou desabilitar o servidor MCP | `true` |
| `MCP_REDIS_CONNECTION` | Nome da conexão Redis do database.php | `default` |

### Exemplo de Configuração .env

```
# Desabilitar servidor MCP em ambientes específicos
MCP_SERVER_ENABLED=false

# Usar uma conexão Redis específica para MCP
MCP_REDIS_CONNECTION=mcp
```

## Licença

Este projeto é distribuído sob a licença MIT.
