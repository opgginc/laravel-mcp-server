<h1 align="center">Laravel MCP Server por OP.GG</h1>

<p align="center">
  Un potente paquete de Laravel para construir un servidor del Protocolo de Contexto de Modelo de forma fluida
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Sitio Web Oficial</a>
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

## ⚠️ Información de Versión y Cambios Disruptivos

### Cambios en v1.3.0 (Actual)

La versión 1.3.0 introduce mejoras en la `ToolInterface` para un mejor control de la comunicación:

**Nuevas Características:**
- Añadido el método `isStreaming(): bool` para una selección más clara del patrón de comunicación
- Herramientas de migración mejoradas que soportan actualizaciones desde v1.1.x, v1.2.x a v1.3.0
- Archivos stub mejorados con documentación completa de v1.3.0

**Características Obsoletas:**
- El método `messageType(): ProcessMessageType` está ahora obsoleto (será eliminado en v2.0.0)
- Usa `isStreaming(): bool` en su lugar para mayor claridad y simplicidad

### Cambios Disruptivos en v1.1.0

La versión 1.1.0 introdujo un cambio significativo y disruptivo en la `ToolInterface`. Si estás actualizando desde v1.0.x, **debes** actualizar tus implementaciones de herramientas para cumplir con la nueva interfaz.

**Cambios Clave en `ToolInterface`:**

La `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` ha sido actualizada de la siguiente manera:

1.  **Nuevo Método Añadido:**

    - `messageType(): ProcessMessageType`
      - Este método es crucial para el nuevo soporte de stream HTTP y determina el tipo de mensaje que se está procesando.

2.  **Renombrado de Métodos:**
    - `getName()` ahora es `name()`
    - `getDescription()` ahora es `description()`
    - `getInputSchema()` ahora es `inputSchema()`
    - `getAnnotations()` ahora es `annotations()`

**Cómo Actualizar tus Herramientas:**

### Migración Automatizada de Herramientas para v1.1.0

Para ayudar con la transición a la nueva `ToolInterface` introducida en v1.1.0, hemos incluido un comando de Artisan que puede ayudar a automatizar la refactorización de tus herramientas existentes:

```bash
php artisan mcp:migrate-tools {path?}
```

**Qué hace:**

Este comando escaneará archivos PHP en el directorio especificado (por defecto `app/MCP/Tools/`) e intentará:

1.  **Identificar herramientas antiguas:** Busca clases que implementen la `ToolInterface` con las firmas de método antiguas.
2.  **Crear Copias de Seguridad:** Antes de hacer cualquier cambio, creará una copia de seguridad de tu archivo de herramienta original con una extensión `.backup` (ej., `YourTool.php.backup`). Si ya existe un archivo de copia de seguridad, el archivo original será omitido para prevenir pérdida accidental de datos.
3.  **Refactorizar la Herramienta:**
    - Renombrar métodos:
      - `getName()` a `name()`
      - `getDescription()` a `description()`
      - `getInputSchema()` a `inputSchema()`
      - `getAnnotations()` a `annotations()`
    - Añadir el nuevo método `messageType()`, que por defecto devolverá `ProcessMessageType::SSE`.
    - Asegurar que la declaración `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` esté presente.

**Uso:**

Después de actualizar el paquete `opgginc/laravel-mcp-server` a v1.1.0 o posterior, si tienes herramientas existentes escritas para v1.0.x, es altamente recomendable ejecutar este comando:

```bash
php artisan mcp:migrate-tools
```

Si tus herramientas están ubicadas en un directorio diferente a `app/MCP/Tools/`, puedes especificar la ruta:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

El comando mostrará su progreso, indicando qué archivos están siendo procesados, respaldados y migrados. Siempre revisa los cambios realizados por la herramienta. Aunque pretende ser precisa, archivos de herramientas complejos o con formato inusual podrían requerir ajustes manuales.

Esta herramienta debería facilitar significativamente el proceso de migración y ayudarte a adaptarte a la nueva estructura de interfaz rápidamente.

### Migración Manual

Si prefieres migrar tus herramientas manualmente, aquí tienes una comparación para ayudarte a adaptar tus herramientas existentes:

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

**`ToolInterface` v1.1.0 (Nueva):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // Nuevo método
    public function name(): string;                     // Renombrado
    public function description(): string;              // Renombrado
    public function inputSchema(): array;               // Renombrado
    public function annotations(): array;               // Renombrado
    public function execute(array $arguments): mixed;   // Sin cambios
}
```

**Ejemplo de una herramienta actualizada:**

Si tu herramienta v1.0.x se veía así:

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

Necesitas actualizarla para v1.1.0 de la siguiente manera:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // Importar el enum

class MyNewTool implements ToolInterface
{
    /**
     * @deprecated desde v1.3.0, usa isStreaming() en su lugar. Será eliminado en v2.0.0
     */
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function isStreaming(): bool
    {
        return false; // La mayoría de herramientas deberían devolver false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Visión General de Laravel MCP Server

Laravel MCP Server es un paquete potente diseñado para agilizar la implementación de servidores del Protocolo de Contexto de Modelo (MCP) en aplicaciones Laravel. **A diferencia de la mayoría de paquetes Laravel MCP que usan transporte de Entrada/Salida Estándar (stdio)**, este paquete se centra en el transporte **HTTP Streamable** y aún incluye un **proveedor SSE legacy** para compatibilidad hacia atrás, proporcionando un método de integración seguro y controlado.

### ¿Por qué HTTP Streamable en lugar de STDIO?

Aunque stdio es directo y ampliamente usado en implementaciones MCP, tiene implicaciones de seguridad significativas para entornos empresariales:

- **Riesgo de Seguridad**: El transporte STDIO potencialmente expone detalles internos del sistema y especificaciones de API
- **Protección de Datos**: Las organizaciones necesitan proteger endpoints de API propietarios y arquitectura interna del sistema
- **Control**: HTTP Streamable ofrece mejor control sobre el canal de comunicación entre clientes LLM y tu aplicación

Al implementar el servidor MCP con transporte HTTP Streamable, las empresas pueden:

- Exponer solo las herramientas y recursos necesarios mientras mantienen privados los detalles de API propietarios
- Mantener control sobre los procesos de autenticación y autorización

Beneficios clave:

- Implementación fluida y rápida de HTTP Streamable en proyectos Laravel existentes
- Soporte para las últimas versiones de Laravel y PHP
- Comunicación eficiente del servidor y procesamiento de datos en tiempo real
- Seguridad mejorada para entornos empresariales

## Características Principales

- Soporte de comunicación en tiempo real a través de HTTP Streamable con integración SSE
- Implementación de herramientas y recursos compatibles con las especificaciones del Protocolo de Contexto de Modelo
- Arquitectura de diseño basada en adaptadores con patrón de mensajería Pub/Sub (comenzando con Redis, más adaptadores planeados)
- Configuración simple de enrutamiento y middleware

### Proveedores de Transporte

La opción de configuración `server_provider` controla qué transporte se usa. Los proveedores disponibles son:

1. **streamable_http** – el predeterminado recomendado. Usa peticiones HTTP estándar y evita problemas con plataformas que cierran conexiones SSE después de aproximadamente un minuto (ej. muchos entornos serverless).
2. **sse** – un proveedor legacy mantenido para compatibilidad hacia atrás. Se basa en conexiones SSE de larga duración y puede no funcionar en plataformas con timeouts HTTP cortos.

El protocolo MCP también define un modo "Streamable HTTP SSE", pero este paquete no lo implementa y no hay planes para hacerlo.

## Requisitos

- PHP >=8.2
- Laravel >=10.x

## Instalación

1. Instala el paquete vía Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Publica el archivo de configuración:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Uso Básico

### Restricción de Dominio

Puedes restringir las rutas del servidor MCP a dominio(s) específico(s) para mejor seguridad y organización:

```php
// config/mcp-server.php

// Permitir acceso desde todos los dominios (predeterminado)
'domain' => null,

// Restringir a un solo dominio
'domain' => 'api.example.com',

// Restringir a múltiples dominios
'domain' => ['api.example.com', 'admin.example.com'],
```

**Cuándo usar restricción de dominio:**
- Ejecutar múltiples aplicaciones en diferentes subdominios
- Separar endpoints de API de tu aplicación principal
- Implementar arquitecturas multi-tenant donde cada tenant tiene su propio subdominio
- Proporcionar los mismos servicios MCP a través de múltiples dominios

**Escenarios de ejemplo:**

```php
// Subdominio de API único
'domain' => 'api.op.gg',

// Múltiples subdominios para diferentes entornos
'domain' => ['api.op.gg', 'staging-api.op.gg'],

// Arquitectura multi-tenant
'domain' => ['tenant1.op.gg', 'tenant2.op.gg', 'tenant3.op.gg'],

// Diferentes servicios en diferentes dominios
'domain' => ['api.op.gg', 'api.kargn.as'],
```

> **Nota:** Cuando uses múltiples dominios, el paquete registra automáticamente rutas separadas para cada dominio para asegurar el enrutamiento adecuado a través de todos los dominios especificados.

### Creando y Añadiendo Herramientas Personalizadas

El paquete proporciona comandos convenientes de Artisan para generar nuevas herramientas:

```bash
php artisan make:mcp-tool MyCustomTool
```

Este comando:

- Maneja varios formatos de entrada (espacios, guiones, mayúsculas mixtas)
- Convierte automáticamente el nombre al formato de caso apropiado
- Crea una clase de herramienta estructurada apropiadamente en `app/MCP/Tools`
- Ofrece registrar automáticamente la herramienta en tu configuración

También puedes crear y registrar herramientas manualmente en `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementación de la herramienta
}
```

### Entendiendo la Estructura de tu Herramienta (ToolInterface)

Cuando creas una herramienta implementando `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, necesitarás definir varios métodos. Aquí tienes un desglose de cada método y su propósito:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    /**
     * @deprecated desde v1.3.0, usa isStreaming() en su lugar. Será eliminado en v2.0.0
     */
    public function messageType(): ProcessMessageType;

    // NUEVO en v1.3.0: Determina si esta herramienta requiere streaming (SSE) en lugar de HTTP estándar.
    public function isStreaming(): bool;

    // El nombre único y llamable de tu herramienta (ej., 'get-user-details').
    public function name(): string;

    // Una descripción legible de lo que hace tu herramienta.
    public function description(): string;

    // Define los parámetros de entrada esperados para tu herramienta usando una estructura similar a JSON Schema.
    public function inputSchema(): array;

    // Proporciona una forma de añadir metadatos arbitrarios o anotaciones a tu herramienta.
    public function annotations(): array;

    // La lógica central de tu herramienta. Recibe argumentos validados y devuelve el resultado.
    public function execute(array $arguments): mixed;
}
```

Profundicemos en algunos de estos métodos:

**`messageType(): ProcessMessageType` (Obsoleto en v1.3.0)**

⚠️ **Este método está obsoleto desde v1.3.0.** Usa `isStreaming(): bool` en su lugar para mayor claridad.

Este método especifica el tipo de procesamiento de mensaje para tu herramienta. Devuelve un valor enum `ProcessMessageType`. Los tipos disponibles son:

- `ProcessMessageType::HTTP`: Para herramientas que interactúan vía petición/respuesta HTTP estándar. Más común para herramientas nuevas.
- `ProcessMessageType::SSE`: Para herramientas específicamente diseñadas para trabajar con Server-Sent Events.

Para la mayoría de herramientas, especialmente aquellas diseñadas para el proveedor primario `streamable_http`, devolverás `ProcessMessageType::HTTP`.

**`isStreaming(): bool` (Nuevo en v1.3.0)**

Este es el nuevo método más intuitivo para controlar patrones de comunicación:

- `return false`: Usar petición/respuesta HTTP estándar (recomendado para la mayoría de herramientas)
- `return true`: Usar Server-Sent Events para streaming en tiempo real

La mayoría de herramientas deberían devolver `false` a menos que específicamente necesites capacidades de streaming en tiempo real como:
- Actualizaciones de progreso en tiempo real para operaciones de larga duración
- Feeds de datos en vivo o herramientas de monitoreo
- Herramientas interactivas que requieren comunicación bidireccional

**`name(): string`**

Este es el identificador para tu herramienta. Debería ser único. Los clientes usarán este nombre para solicitar tu herramienta. Por ejemplo: `get-weather`, `calculate-sum`.

**`description(): string`**

Una descripción clara y concisa de la funcionalidad de tu herramienta. Esto se usa en documentación, y las UIs de cliente MCP (como el MCP Inspector) pueden mostrarlo a los usuarios.

**`inputSchema(): array`**

Este método es crucial para definir los parámetros de entrada esperados de tu herramienta. Debería devolver un array que siga una estructura similar a JSON Schema. Este esquema se usa:

- Por clientes para entender qué datos enviar.
- Potencialmente por el servidor o cliente para validación de entrada.
- Por herramientas como el MCP Inspector para generar formularios para pruebas.

**Ejemplo de `inputSchema()`:**

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
                'default' => false, // Puedes especificar valores predeterminados
            ],
        ],
        'required' => ['userId'], // Especifica qué propiedades son obligatorias
    ];
}
```

En tu método `execute`, puedes entonces validar los argumentos entrantes. El ejemplo `HelloWorldTool` usa `Illuminate\Support\Facades\Validator` para esto:

```php
// Dentro de tu método execute():
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
// Proceder con $arguments['userId'] y $arguments['includeDetails'] validados
```

**`annotations(): array`**

Este método proporciona metadatos sobre el comportamiento y características de tu herramienta, siguiendo la [especificación oficial de Anotaciones de Herramientas MCP](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Las anotaciones ayudan a los clientes MCP a categorizar herramientas, tomar decisiones informadas sobre la aprobación de herramientas, y proporcionar interfaces de usuario apropiadas.

**Anotaciones MCP Estándar:**

El Protocolo de Contexto de Modelo define varias anotaciones estándar que los clientes entienden:

- **`title`** (string): Un título legible para la herramienta, mostrado en UIs de cliente
- **`readOnlyHint`** (boolean): Indica si la herramienta solo lee datos sin modificar el entorno (predeterminado: false)
- **`destructiveHint`** (boolean): Sugiere si la herramienta puede realizar operaciones destructivas como eliminar datos (predeterminado: true)
- **`idempotentHint`** (boolean): Indica si llamadas repetidas con los mismos argumentos no tienen efecto adicional (predeterminado: false)
- **`openWorldHint`** (boolean): Señala si la herramienta interactúa con entidades externas más allá del entorno local (predeterminado: true)

**Importante:** Estas son pistas, no garantías. Ayudan a los clientes a proporcionar mejores experiencias de usuario pero no deberían usarse para decisiones críticas de seguridad.

**Ejemplo con anotaciones MCP estándar:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // La herramienta solo lee datos de usuario
        'destructiveHint' => false,    // La herramienta no elimina o modifica datos
        'idempotentHint' => true,      // Seguro de llamar múltiples veces
        'openWorldHint' => false,      // La herramienta solo accede a la base de datos local
    ];
}
```

**Ejemplos del mundo real por tipo de herramienta:**

```php
// Herramienta de consulta de base de datos
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

// Herramienta de eliminación de posts
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Puede eliminar posts
        'idempotentHint' => false,     // Eliminar dos veces tiene efectos diferentes
        'openWorldHint' => false,
    ];
}

// Herramienta de integración de API
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Accede a API externa de clima
    ];
}
```

**Anotaciones personalizadas** también pueden añadirse para las necesidades específicas de tu aplicación:

```php
public function annotations(): array
{
    return [
        // Anotaciones MCP estándar
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // Anotaciones personalizadas para tu aplicación
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Trabajando con Recursos

Los recursos exponen datos de tu servidor que pueden ser leídos por clientes MCP. Son
**controlados por la aplicación**, lo que significa que el cliente decide cuándo y cómo usarlos.
Crea recursos concretos o plantillas URI en `app/MCP/Resources` y
`app/MCP/ResourceTemplates` usando los helpers de Artisan:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

Registra las clases generadas en `config/mcp-server.php` bajo los arrays `resources`
y `resource_templates`. Cada clase de recurso extiende la clase base
`Resource` e implementa un método `read()` que devuelve contenido `text` o
`blob`. Las plantillas extienden `ResourceTemplate` y describen patrones URI
dinámicos que los clientes pueden usar. Un recurso se identifica por una URI como
`file:///logs/app.log` y opcionalmente puede definir metadatos como `mimeType` o
`size`.

**Plantillas de Recursos con Listado Dinámico**: Las plantillas pueden opcionalmente implementar un método `list()` para proporcionar instancias de recursos concretos que coincidan con el patrón de la plantilla. Esto permite a los clientes descubrir recursos disponibles dinámicamente. El método `list()` permite a las instancias de ResourceTemplate generar una lista de recursos específicos que pueden ser leídos a través del método `read()` de la plantilla.

Lista recursos disponibles usando el endpoint `resources/list` y lee su
contenido con `resources/read`. El endpoint `resources/list` devuelve un array
de recursos concretos, incluyendo tanto recursos estáticos como recursos generados
dinámicamente desde plantillas que implementan el método `list()`:

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

**Lectura Dinámica de Recursos**: Las plantillas de recursos soportan patrones de plantilla URI (RFC 6570) que permiten a los clientes construir identificadores de recursos dinámicos. Cuando un cliente solicita una URI de recurso que coincide con un patrón de plantilla, el método `read()` de la plantilla es llamado con parámetros extraídos para generar el contenido del recurso.

Flujo de trabajo de ejemplo:
1. La plantilla define el patrón: `"database://users/{userId}/profile"`
2. El cliente solicita: `"database://users/123/profile"`
3. La plantilla extrae `{userId: "123"}` y llama al método `read()`
4. La plantilla devuelve datos de perfil de usuario para el ID de usuario 123

También puedes listar plantillas por separado usando el endpoint `resources/templates/list`:

```bash
# Listar solo plantillas de recursos
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

Cuando ejecutes tu servidor Laravel MCP remotamente, el transporte HTTP funciona con
peticiones JSON-RPC estándar. Aquí tienes un ejemplo simple usando `curl` para listar y
leer recursos:

```bash
# Listar recursos
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# Leer un recurso específico
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

El servidor responde con mensajes JSON transmitidos sobre la conexión HTTP, así que
`curl --no-buffer` puede usarse si quieres ver salida incremental.

### Trabajando con Prompts

Los prompts proporcionan fragmentos de texto reutilizables con soporte de argumentos que tus herramientas o usuarios pueden solicitar.
Crea clases de prompt en `app/MCP/Prompts` usando:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

Regístralos en `config/mcp-server.php` bajo `prompts`. Cada clase de prompt
extiende la clase base `Prompt` y define:
- `name`: Identificador único (ej., "welcome-user")
- `description`: Descripción opcional legible
- `arguments`: Array de definiciones de argumentos con campos name, description y required
- `text`: La plantilla de prompt con marcadores de posición como `{username}`

Lista prompts vía el endpoint `prompts/list` y obténlos usando
`prompts/get` con argumentos:

```bash
# Obtener un prompt de bienvenida con argumentos
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### Prompts MCP

Al crear prompts que referencien tus herramientas o recursos, consulta las [directrices oficiales de prompts](https://modelcontextprotocol.io/docs/concepts/prompts). Los prompts son plantillas reutilizables que pueden aceptar argumentos, incluir contexto de recursos e incluso describir flujos de trabajo de múltiples pasos.

**Estructura de prompt**

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

Los clientes descubren prompts vía `prompts/list` y solicitan específicos con `prompts/get`:

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

**Ejemplo de Clase Prompt**

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

Los prompts pueden embeber recursos y devolver secuencias de mensajes para guiar un LLM. Ve la documentación oficial para ejemplos avanzados y mejores prácticas.

### Trabajando con Notificaciones

Las notificaciones son mensajes fire-and-forget de clientes MCP que siempre devuelven HTTP 202 Accepted sin cuerpo de respuesta. Son perfectas para logging, seguimiento de progreso, manejo de eventos y activación de procesos en segundo plano sin bloquear al cliente.

#### Creando Manejadores de Notificaciones

**Uso básico del comando:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**Características avanzadas del comando:**

```bash
# Modo interactivo - solicita método si no se especifica
php artisan make:mcp-notification MyHandler

# Manejo automático de prefijo de método
php artisan make:mcp-notification StatusHandler --method=status  # se convierte en notifications/status

# Normalización de nombre de clase 
php artisan make:mcp-notification "user activity"  # se convierte en UserActivityHandler
```

El comando proporciona:
- **Solicitud interactiva de método** cuando no se especifica `--method`
- **Guía de registro automático** con código listo para copiar y pegar
- **Ejemplos de prueba incorporados** con comandos curl 
- **Instrucciones de uso completas** y casos de uso comunes

#### Arquitectura de Manejador de Notificaciones

Cada manejador de notificaciones debe implementar la clase abstracta `NotificationHandler`:

```php
abstract class NotificationHandler
{
    // Requerido: Tipo de mensaje (usualmente ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // Requerido: El método de notificación a manejar  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // Requerido: Ejecutar la lógica de notificación
    abstract public function execute(?array $params = null): void;
}
```

**Componentes arquitectónicos clave:**

- **`MESSAGE_TYPE`**: Usualmente `ProcessMessageType::HTTP` para notificaciones estándar
- **`HANDLE_METHOD`**: El método JSON-RPC que procesa este manejador (debe comenzar con `notifications/`)
- **`execute()`**: Contiene tu lógica de notificación - devuelve void (no se envía respuesta)
- **Validación del constructor**: Valida automáticamente que las constantes requeridas estén definidas

#### Manejadores de Notificaciones Incorporados

El paquete incluye cuatro manejadores pre-construidos para escenarios MCP comunes:

**1. InitializedHandler (`notifications/initialized`)**
- **Propósito**: Procesa confirmaciones de inicialización del cliente después de handshake exitoso
- **Parámetros**: Información y capacidades del cliente
- **Uso**: Seguimiento de sesiones, logging de cliente, eventos de inicialización

**2. ProgressHandler (`notifications/progress`)**
- **Propósito**: Maneja actualizaciones de progreso para operaciones de larga duración
- **Parámetros**: 
  - `progressToken` (string): Identificador único para la operación
  - `progress` (number): Valor de progreso actual
  - `total` (number, opcional): Valor total de progreso para cálculo de porcentaje
- **Uso**: Seguimiento de progreso en tiempo real, monitoreo de cargas, finalización de tareas

**3. CancelledHandler (`notifications/cancelled`)**
- **Propósito**: Procesa notificaciones de cancelación de solicitudes
- **Parámetros**:
  - `requestId` (string): ID de la solicitud a cancelar
  - `reason` (string, opcional): Razón de cancelación
- **Uso**: Terminación de trabajos en segundo plano, limpieza de recursos, aborto de operaciones

**4. MessageHandler (`notifications/message`)**
- **Propósito**: Maneja mensajes generales de logging y comunicación
- **Parámetros**:
  - `level` (string): Nivel de log (info, warning, error, debug)
  - `message` (string): El contenido del mensaje
  - `logger` (string, opcional): Nombre del logger
- **Uso**: Logging del lado del cliente, depuración, comunicación general

#### Ejemplos de Manejadores para Escenarios Comunes

```php
// Seguimiento de progreso de carga de archivos
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
            
            // Transmitir actualización en tiempo real
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// Actividad de usuario y logging de auditoría
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
        
        // Activar alertas de seguridad para acciones sensibles
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// Activación de tareas en segundo plano
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

#### Registrando Manejadores de Notificaciones

**En tu proveedor de servicios:**

```php
// En AppServiceProvider o proveedor de servicios MCP dedicado
public function boot()
{
    $server = app(MCPServer::class);
    
    // Registrar manejadores incorporados (opcional - se registran por defecto)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // Registrar manejadores personalizados
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### Probando Notificaciones

**Usando curl para probar manejadores de notificaciones:**

```bash
# Probar notificación de progreso
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
# Esperado: HTTP 202 con cuerpo vacío

# Probar notificación de actividad de usuario  
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
# Esperado: HTTP 202 con cuerpo vacío

# Probar notificación de cancelación
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
# Esperado: HTTP 202 con cuerpo vacío
```

**Notas importantes de prueba:**
- Las notificaciones devuelven **HTTP 202** (nunca 200)
- El cuerpo de respuesta está **siempre vacío**
- No se envía mensaje de respuesta JSON-RPC
- Verificar logs del servidor para confirmar procesamiento de notificaciones

#### Manejo de Errores y Validación

**Patrones de validación comunes:**

```php
public function execute(?array $params = null): void
{
    // Validar parámetros requeridos
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // No lances excepción - las notificaciones deben ser tolerantes a fallos
    }
    
    // Validar tipos de parámetros
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // Extracción segura de parámetros con valores por defecto
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // Procesar notificación...
}
```

**Mejores prácticas de manejo de errores:**
- **Registrar errores** en lugar de lanzar excepciones
- **Usar programación defensiva** con verificaciones null y valores por defecto
- **Fallar elegantemente** - no romper el flujo de trabajo del cliente
- **Validar entradas** pero continuar procesando cuando sea posible
- **Monitorear notificaciones** a través de logging y métricas

### Probando Herramientas MCP

El paquete incluye un comando especial para probar tus herramientas MCP sin necesidad de un cliente MCP real:

```bash
# Probar una herramienta específica interactivamente
php artisan mcp:test-tool MyCustomTool

# Listar todas las herramientas disponibles
php artisan mcp:test-tool --list

# Probar con entrada JSON específica
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

Esto te ayuda a desarrollar y depurar herramientas rápidamente:

- Mostrando el esquema de entrada de la herramienta y validando entradas
- Ejecutando la herramienta con tu entrada proporcionada
- Mostrando resultados formateados o información detallada de errores
- Soportando tipos de entrada complejos incluyendo objetos y arrays

### Visualizando Herramientas MCP con Inspector

También puedes usar el Inspector del Protocolo de Contexto de Modelo para visualizar y probar tus herramientas MCP:

```bash
# Ejecutar el Inspector MCP sin instalación
npx @modelcontextprotocol/inspector node build/index.js
```

Esto típicamente abrirá una interfaz web en `localhost:6274`. Para probar tu servidor MCP:

1. **Advertencia**: `php artisan serve` NO PUEDE usarse con este paquete porque no puede manejar múltiples conexiones PHP simultáneamente. Dado que MCP SSE requiere procesar múltiples conexiones concurrentemente, debes usar una de estas alternativas:

   - **Laravel Octane** (Opción más fácil):

     ```bash
     # Instalar y configurar Laravel Octane con FrankenPHP (recomendado)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Iniciar el servidor Octane
     php artisan octane:start
     ```

     > **Importante**: Al instalar Laravel Octane, asegúrate de usar FrankenPHP como servidor. El paquete puede no funcionar correctamente con RoadRunner debido a problemas de compatibilidad con conexiones SSE. Si puedes ayudar a arreglar este problema de compatibilidad con RoadRunner, por favor envía un Pull Request - ¡tu contribución sería muy apreciada!

     Para detalles, ve la [documentación de Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opciones de grado de producción**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Configuración Docker personalizada

   * Cualquier servidor web que soporte apropiadamente streaming SSE (requerido solo para el proveedor SSE legacy)

2. En la interfaz del Inspector, introduce la URL del endpoint MCP de tu servidor Laravel (ej., `http://localhost:8000/mcp`). Si estás usando el proveedor SSE legacy, usa la URL SSE en su lugar (`http://localhost:8000/mcp/sse`).
3. Conéctate y explora las herramientas disponibles visualmente

El endpoint MCP sigue el patrón: `http://[tu-servidor-laravel]/[ruta_predeterminada]` donde `ruta_predeterminada` está definida en tu archivo `config/mcp-server.php`.

## Características Avanzadas

### Arquitectura Pub/Sub con Adaptadores SSE (proveedor legacy)

El paquete implementa un patrón de mensajería publicar/suscribir (pub/sub) a través de su sistema de adaptadores:

1. **Publicador (Servidor)**: Cuando los clientes envían peticiones al endpoint `/message`, el servidor procesa estas peticiones y publica respuestas a través del adaptador configurado.

2. **Broker de Mensajes (Adaptador)**: El adaptador (ej., Redis) mantiene colas de mensajes para cada cliente, identificados por IDs de cliente únicos. Esto proporciona una capa de comunicación asíncrona confiable.

3. **Suscriptor (conexión SSE)**: Las conexiones SSE de larga duración se suscriben a mensajes para sus respectivos clientes y los entregan en tiempo real. Esto aplica solo cuando se usa el proveedor SSE legacy.

Esta arquitectura permite:

- Comunicación escalable en tiempo real
- Entrega confiable de mensajes incluso durante desconexiones temporales
- Manejo eficiente de múltiples conexiones de cliente concurrentes
- Potencial para despliegues de servidor distribuidos

### Configuración del Adaptador Redis

El adaptador Redis predeterminado puede configurarse de la siguiente manera:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefijo para claves Redis
        'connection' => 'default', // Conexión Redis desde database.php
        'ttl' => 100,              // TTL de mensaje en segundos
    ],
],
```

## Traducir README.md

Para traducir este README a otros idiomas usando la API de Claude (Procesamiento paralelo):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

También puedes traducir idiomas específicos:

```bash
python scripts/translate_readme.py es ko
```

## Características Obsoletas para v2.0.0

Las siguientes características están obsoletas y serán eliminadas en v2.0.0. Por favor actualiza tu código en consecuencia:

### Cambios en ToolInterface

**Obsoleto desde v1.3.0:**
- Método `messageType(): ProcessMessageType`
- **Reemplazo:** Usa `isStreaming(): bool` en su lugar
- **Guía de Migración:** Devuelve `false` para herramientas HTTP, `true` para herramientas de streaming
- **Migración Automática:** Ejecuta `php artisan mcp:migrate-tools` para actualizar tus herramientas

**Ejemplo de Migración:**

```php
// Enfoque antiguo (obsoleto)
public function messageType(): ProcessMessageType
{
    return ProcessMessageType::HTTP;
}

// Nuevo enfoque (v1.3.0+)
public function isStreaming(): bool
{
    return false; // Usa false para HTTP, true para streaming
}
```

### Características Eliminadas

**Eliminado en v1.3.0:**
- Caso enum `ProcessMessageType::PROTOCOL` (consolidado en `ProcessMessageType::HTTP`)

**Planeado para v2.0.0:**
- Eliminación completa del método `messageType()` de `ToolInterface`
- Todas las herramientas serán requeridas a implementar solo el método `isStreaming()`
- Configuración de herramientas simplificada y complejidad reducida

## Licencia

Este proyecto se distribuye bajo la licencia MIT.