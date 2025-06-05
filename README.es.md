<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Un potente paquete de Laravel para construir un Servidor de Protocolo de Contexto de Modelo de forma fluida
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

## ⚠️ Cambios Incompatibles en v1.1.0

La versión 1.1.0 introdujo un cambio significativo e incompatible en la `ToolInterface`. Si estás actualizando desde v1.0.x, **debes** actualizar tus implementaciones de herramientas para cumplir con la nueva interfaz.

**Cambios Clave en `ToolInterface`:**

La `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` ha sido actualizada de la siguiente manera:

1.  **Nuevo Método Añadido:**

    - `messageType(): ProcessMessageType`
      - Este método es crucial para el nuevo soporte de HTTP stream y determina el tipo de mensaje que se está procesando.

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

**Lo que hace:**

Este comando escaneará archivos PHP en el directorio especificado (por defecto `app/MCP/Tools/`) e intentará:

1.  **Identificar herramientas antiguas:** Busca clases que implementen la `ToolInterface` con las firmas de métodos antiguas.
2.  **Crear Copias de Seguridad:** Antes de hacer cualquier cambio, creará una copia de seguridad de tu archivo de herramienta original con una extensión `.backup` (ej. `YourTool.php.backup`). Si ya existe un archivo de copia de seguridad, el archivo original será omitido para prevenir pérdida accidental de datos.
3.  **Refactorizar la Herramienta:**
    - Renombrar métodos:
      - `getName()` a `name()`
      - `getDescription()` a `description()`
      - `getInputSchema()` a `inputSchema()`
      - `getAnnotations()` a `annotations()`
    - Añadir el nuevo método `messageType()`, que por defecto devolverá `ProcessMessageType::SSE`.
    - Asegurar que la declaración `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` esté presente.

**Uso:**

Después de actualizar el paquete `opgginc/laravel-mcp-server` a v1.1.0 o posterior, si tienes herramientas existentes escritas para v1.0.x, es muy recomendable ejecutar este comando:

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
    // Añadir el nuevo método messageType()
    public function messageType(): ProcessMessageType
    {
        // Devolver el tipo de mensaje apropiado, ej. para una herramienta estándar
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // Renombrado
    public function description(): string { return 'This is my new tool.'; } // Renombrado
    public function inputSchema(): array { return []; } // Renombrado
    public function annotations(): array { return []; } // Renombrado
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Visión General de Laravel MCP Server

Laravel MCP Server es un paquete potente diseñado para agilizar la implementación de servidores de Protocolo de Contexto de Modelo (MCP) en aplicaciones Laravel. **A diferencia de la mayoría de paquetes Laravel MCP que usan transporte de Entrada/Salida Estándar (stdio)**, este paquete se centra en el transporte **HTTP Streamable** y aún incluye un **proveedor SSE legacy** para compatibilidad hacia atrás, proporcionando un método de integración seguro y controlado.

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
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider" --no-interaction
   ```

## Uso Básico

### Creando y Añadiendo Herramientas Personalizadas

El paquete proporciona comandos convenientes de Artisan para generar nuevas herramientas:

```bash
php artisan make:mcp-tool MyCustomTool
```

Este comando:

- Maneja varios formatos de entrada (espacios, guiones, mayúsculas mixtas)
- Convierte automáticamente el nombre al formato de caso apropiado
- Crea una clase de herramienta correctamente estructurada en `app/MCP/Tools`
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
    // Determina cómo se procesan los mensajes de la herramienta, a menudo relacionado con el transporte.
    public function messageType(): ProcessMessageType;

    // El nombre único y llamable de tu herramienta (ej. 'get-user-details').
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

**`messageType(): ProcessMessageType`**

Este método especifica el tipo de procesamiento de mensajes para tu herramienta. Devuelve un valor enum `ProcessMessageType`. Los tipos disponibles son:

- `ProcessMessageType::HTTP`: Para herramientas que interactúan vía petición/respuesta HTTP estándar. Más común para herramientas nuevas.
- `ProcessMessageType::SSE`: Para herramientas específicamente diseñadas para trabajar con Server-Sent Events.

Para la mayoría de herramientas, especialmente aquellas diseñadas para el proveedor principal `streamable_http`, devolverás `ProcessMessageType::HTTP`.

**`name(): string`**

Este es el identificador para tu herramienta. Debe ser único. Los clientes usarán este nombre para solicitar tu herramienta. Por ejemplo: `get-weather`, `calculate-sum`.

**`description(): string`**

Una descripción clara y concisa de la funcionalidad de tu herramienta. Esto se usa en documentación, y las UIs de clientes MCP (como el MCP Inspector) pueden mostrarlo a los usuarios.

**`inputSchema(): array`**

Este método es crucial para definir los parámetros de entrada esperados de tu herramienta. Debe devolver un array que siga una estructura similar a JSON Schema. Este esquema se usa:

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
                'default' => false, // Puedes especificar valores por defecto
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
// Continúa con $arguments['userId'] y $arguments['includeDetails'] validados
```

**`annotations(): array`**

Este método proporciona metadatos sobre el comportamiento y características de tu herramienta, siguiendo la [especificación oficial de Anotaciones de Herramientas MCP](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Las anotaciones ayudan a los clientes MCP a categorizar herramientas, tomar decisiones informadas sobre aprobación de herramientas, y proporcionar interfaces de usuario apropiadas.

**Anotaciones MCP Estándar:**

El Protocolo de Contexto de Modelo define varias anotaciones estándar que los clientes entienden:

- **`title`** (string): Un título legible para la herramienta, mostrado en UIs de cliente
- **`readOnlyHint`** (boolean): Indica si la herramienta solo lee datos sin modificar el entorno (predeterminado: false)
- **`destructiveHint`** (boolean): Sugiere si la herramienta puede realizar operaciones destructivas como eliminar datos (predeterminado: true)
- **`idempotentHint`** (boolean): Indica si llamadas repetidas con los mismos argumentos no tienen efecto adicional (predeterminado: false)
- **`openWorldHint`** (boolean): Señala si la herramienta interactúa con entidades externas más allá del entorno local (predeterminado: true)

**Importante:** Estas son pistas, no garantías. Ayudan a los clientes a proporcionar mejores experiencias de usuario pero no deben usarse para decisiones críticas de seguridad.

**Ejemplo con anotaciones MCP estándar:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // La herramienta solo lee datos de usuario
        'destructiveHint' => false,    // La herramienta no elimina o modifica datos
        'idempotentHint' => true,      // Seguro llamar múltiples veces
        'openWorldHint' => false,      // La herramienta solo accede a base de datos local
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

// Herramienta de integración API
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

También puedes usar el Model Context Protocol Inspector para visualizar y probar tus herramientas MCP:

```bash
# Ejecutar el MCP Inspector sin instalación
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

     Para detalles, consulta la [documentación de Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opciones de grado de producción**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Configuración Docker personalizada

   * Cualquier servidor web que soporte apropiadamente streaming SSE (requerido solo para el proveedor SSE legacy)

2. En la interfaz del Inspector, introduce la URL del endpoint MCP de tu servidor Laravel (ej. `http://localhost:8000/mcp`). Si estás usando el proveedor SSE legacy, usa la URL SSE en su lugar (`http://localhost:8000/mcp/sse`).
3. Conéctate y explora las herramientas disponibles visualmente

El endpoint MCP sigue el patrón: `http://[tu-servidor-laravel]/[default_path]` donde `default_path` está definido en tu archivo `config/mcp-server.php`.

## Características Avanzadas

### Arquitectura Pub/Sub con Adaptadores SSE (proveedor legacy)

El paquete implementa un patrón de mensajería publicar/suscribir (pub/sub) a través de su sistema de adaptadores:

1. **Publicador (Servidor)**: Cuando los clientes envían peticiones al endpoint `/message`, el servidor procesa estas peticiones y publica respuestas a través del adaptador configurado.

2. **Broker de Mensajes (Adaptador)**: El adaptador (ej. Redis) mantiene colas de mensajes para cada cliente, identificados por IDs únicos de cliente. Esto proporciona una capa de comunicación asíncrona confiable.

3. **Suscriptor (conexión SSE)**: Las conexiones SSE de larga duración se suscriben a mensajes para sus respectivos clientes y los entregan en tiempo real. Esto aplica solo cuando se usa el proveedor SSE legacy.

Esta arquitectura permite:

- Comunicación escalable en tiempo real
- Entrega confiable de mensajes incluso durante desconexiones temporales
- Manejo eficiente de múltiples conexiones concurrentes de clientes
- Potencial para despliegues distribuidos de servidores

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

## Variables de Entorno

El paquete soporta las siguientes variables de entorno para permitir configuración sin modificar los archivos de configuración:

| Variable               | Descripción                                      | Predeterminado |
| ---------------------- | ------------------------------------------------ | -------------- |
| `MCP_SERVER_ENABLED`   | Habilitar o deshabilitar el servidor MCP        | `true`         |

### Ejemplo de Configuración .env

```
# Deshabilitar servidor MCP en entornos específicos
MCP_SERVER_ENABLED=false
```

## Traducir README.md

Para traducir este README a otros idiomas usando Claude API (Procesamiento paralelo):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

También puedes traducir idiomas específicos:

```bash
python scripts/translate_readme.py es ko
```

## Licencia

Este proyecto se distribuye bajo la licencia MIT.
