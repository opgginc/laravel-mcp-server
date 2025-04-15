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

Laravel MCP Server é um pacote robusto desenvolvido para facilitar a implementação de servidores de Protocolo de Contexto de Modelo (MCP) em aplicações Laravel. **Diferente da maioria dos pacotes Laravel MCP que usam transporte de Entrada/Saída Padrão (stdio)**, este pacote **utiliza transporte de Eventos Enviados pelo Servidor (SSE)**, oferecendo um método de integração mais seguro e controlado.

### Por que SSE ao invés de STDIO?

Embora o stdio seja direto e amplamente usado em implementações MCP, ele apresenta implicações significativas de segurança para ambientes empresariais:

- **Risco de Segurança**: O transporte STDIO potencialmente expõe detalhes internos do sistema e especificações de API
- **Proteção de Dados**: Organizações precisam proteger endpoints de API proprietários e arquitetura interna do sistema
- **Controle**: SSE oferece melhor controle sobre o canal de comunicação entre clientes LLM e sua aplicação

Ao implementar o servidor MCP com transporte SSE, empresas podem:

- Expor apenas as ferramentas e recursos necessários mantendo privados os detalhes proprietários da API
- Manter controle sobre processos de autenticação e autorização

Principais benefícios:

- Implementação rápida e simples de SSE em projetos Laravel existentes
- Suporte para as versões mais recentes de Laravel e PHP
- Comunicação eficiente do servidor e processamento de dados em tempo real
- Segurança aprimorada para ambientes empresariais

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

Este comando:

- Lida com vários formatos de entrada (espaços, hífens, case misto)
- Converte automaticamente o nome para o formato de case adequado
- Cria uma classe de ferramenta estruturada corretamente em `app/MCP/Tools`
- Oferece a opção de registrar automaticamente a ferramenta na sua configuração

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

### Visualizando Ferramentas MCP com o Inspector

Você também pode usar o Model Context Protocol Inspector para visualizar e testar suas ferramentas MCP:

```bash
# Execute o MCP Inspector sem instalação
npx @modelcontextprotocol/inspector node build/index.js
```

Isso normalmente abrirá uma interface web em `localhost:6274`. Para testar seu servidor MCP:

1. Inicie seu servidor de desenvolvimento Laravel (ex: `php artisan serve`)
2. Na interface do Inspector, insira a URL SSE do seu servidor Laravel (ex: `http://localhost:8000/mcp/sse`)
3. Conecte-se e explore visualmente as ferramentas disponíveis

A URL SSE segue o padrão: `http://[seu-servidor-laravel]/[default_path]/sse` onde `default_path` é definido no seu arquivo `config/mcp-server.php`.

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
