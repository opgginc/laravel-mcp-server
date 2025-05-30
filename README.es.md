<h1 align="center">Laravel MCP Server por OP.GG</h1>

<p align="center">
  Un potente paquete de Laravel para construir un Servidor de Protocolo de Contexto de Modelo sin problemas
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

## Descripción General

Laravel MCP Server es un potente paquete diseñado para simplificar la implementación de servidores de Protocolo de Contexto de Modelo (MCP) en aplicaciones Laravel. **A diferencia de la mayoría de los paquetes Laravel MCP que utilizan transporte de Entrada/Salida Estándar (stdio)**, este paquete se centra en **Streamable HTTP** y mantiene un **proveedor SSE heredado** para compatibilidad, ofreciendo así un método de integración seguro y controlado.

### ¿Por qué Streamable HTTP en lugar de STDIO?

Aunque stdio es sencillo y ampliamente utilizado en implementaciones MCP, tiene implicaciones de seguridad significativas para entornos empresariales:

- **Riesgo de Seguridad**: El transporte STDIO potencialmente expone detalles internos del sistema y especificaciones de API
- **Protección de Datos**: Las organizaciones necesitan proteger los puntos finales de API propietarios y la arquitectura interna del sistema
- **Control**: Streamable HTTP ofrece mejor control sobre el canal de comunicación entre clientes LLM y su aplicación

Al implementar el servidor MCP con Streamable HTTP, las empresas pueden:

- Exponer solo las herramientas y recursos necesarios mientras mantienen privados los detalles de API propietarios
- Mantener control sobre los procesos de autenticación y autorización

Beneficios clave:

- Implementación rápida y sin problemas de Streamable HTTP en proyectos Laravel existentes
- Soporte para las últimas versiones de Laravel y PHP
- Comunicación eficiente del servidor y procesamiento de datos en tiempo real
- Seguridad mejorada para entornos empresariales

## Características Principales

- Soporte de comunicación en tiempo real mediante Streamable HTTP y SSE (proveedor heredado)
- Implementación de herramientas y recursos conformes con las especificaciones del Protocolo de Contexto de Modelo
- Arquitectura de diseño basada en adaptadores con patrón de mensajería Pub/Sub (comenzando con Redis, más adaptadores planificados)
- Configuración simple de rutas y middleware

### Proveedores de transporte

La opción `server_provider` del archivo de configuración define qué transporte se usa. Existen dos valores:

1. **streamable_http** – recomendado por defecto. Usa solicitudes HTTP normales y evita problemas en plataformas donde las conexiones SSE se cierran tras unos 60 segundos (como muchos entornos serverless).
2. **sse** – proveedor heredado para compatibilidad. Requiere conexiones SSE de larga duración y puede fallar en esas plataformas.

El modo "Streamable HTTP SSE" descrito por el protocolo MCP no está implementado en este paquete ni hay planes para hacerlo.

## Requisitos

- PHP >=8.2
- Laravel >=10.x

## Instalación

1. Instale el paquete vía Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Publique el archivo de configuración:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Uso Básico

### Creación y Adición de Herramientas Personalizadas

El paquete proporciona comandos Artisan convenientes para generar nuevas herramientas:

```bash
php artisan make:mcp-tool MiHerramientaPersonalizada
```

Este comando:

- Maneja varios formatos de entrada (espacios, guiones, casos mixtos)
- Convierte automáticamente el nombre al formato de caso apropiado
- Crea una clase de herramienta correctamente estructurada en `app/MCP/Tools`
- Ofrece registrar automáticamente la herramienta en su configuración

También puede crear y registrar herramientas manualmente en `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MiHerramientaPersonalizada implements ToolInterface
{
    // Implementación de la herramienta
}
```

### Prueba de Herramientas MCP

El paquete incluye un comando especial para probar sus herramientas MCP sin necesidad de un cliente MCP real:

```bash
# Probar una herramienta específica interactivamente
php artisan mcp:test-tool MiHerramientaPersonalizada

# Listar todas las herramientas disponibles
php artisan mcp:test-tool --list

# Probar con entrada JSON específica
php artisan mcp:test-tool MiHerramientaPersonalizada --input='{"param":"valor"}'
```

Esto le ayuda a desarrollar y depurar herramientas rápidamente al:

- Mostrar el esquema de entrada de la herramienta y validar entradas
- Ejecutar la herramienta con su entrada proporcionada
- Mostrar resultados formateados o información detallada de errores
- Soportar tipos de entrada complejos incluyendo objetos y arreglos

### Visualización de Herramientas MCP con Inspector

También puede utilizar el Inspector de Protocolo de Contexto de Modelo para visualizar y probar sus herramientas MCP:

```bash
# Ejecutar el Inspector MCP sin instalación
npx @modelcontextprotocol/inspector node build/index.js
```

Esto normalmente abrirá una interfaz web en `localhost:6274`. Para probar su servidor MCP:

1. **Advertencia**: `php artisan serve` NO PUEDE ser usado con este paquete porque no puede manejar múltiples conexiones PHP simultáneamente. Como MCP SSE requiere procesar múltiples conexiones concurrentemente, debe usar una de estas alternativas:

   * **Laravel Octane** (Opción más fácil):
     ```bash
     # Instalar y configurar Laravel Octane con FrankenPHP (recomendado)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp
     
     # Iniciar el servidor Octane
     php artisan octane:start
     ```
     
     > **Importante**: Al instalar Laravel Octane, asegúrese de usar FrankenPHP como servidor. El paquete puede no funcionar correctamente con RoadRunner debido a problemas de compatibilidad con conexiones SSE. Si puede ayudar a solucionar este problema de compatibilidad con RoadRunner, envíe un Pull Request - ¡su contribución sería muy apreciada!
     
     Para detalles, consulte la [documentación de Laravel Octane](https://laravel.com/docs/12.x/octane)
     
   * **Opciones de nivel de producción**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Configuración personalizada de Docker
    - Cualquier servidor web que soporte adecuadamente streaming SSE (solo necesario con el proveedor SSE heredado)

2. En la interfaz del Inspector, ingrese la URL del endpoint MCP de su servidor Laravel (ej., `http://localhost:8000/mcp`). Si usa el proveedor SSE heredado, utilice la URL SSE (`http://localhost:8000/mcp/sse`).
3. Conéctese y explore visualmente las herramientas disponibles

El endpoint MCP sigue el patrón: `http://[su-servidor-laravel]/[ruta_predeterminada]` donde `ruta_predeterminada` se define en su archivo `config/mcp-server.php`.

## Funcionalidades Avanzadas

### Arquitectura Pub/Sub con Adaptadores SSE (proveedor heredado)

El paquete implementa un patrón de mensajería publicador/suscriptor (pub/sub) a través de su sistema de adaptadores:

1. **Publicador (Servidor)**: Cuando los clientes envían solicitudes al punto final `/message`, el servidor procesa estas solicitudes y publica respuestas a través del adaptador configurado.

2. **Intermediario de Mensajes (Adaptador)**: El adaptador (ej., Redis) mantiene colas de mensajes para cada cliente, identificados por IDs de cliente únicos. Esto proporciona una capa de comunicación asíncrona confiable.

3. **Suscriptor (Conexión SSE)**: Las conexiones SSE de larga duración se suscriben a mensajes para sus respectivos clientes y los entregan en tiempo real. Esto aplica solo cuando se usa el proveedor SSE heredado.

Esta arquitectura permite:

- Comunicación escalable en tiempo real
- Entrega confiable de mensajes incluso durante desconexiones temporales
- Manejo eficiente de múltiples conexiones de clientes concurrentes
- Potencial para despliegues de servidores distribuidos

### Configuración del Adaptador Redis

El adaptador Redis predeterminado puede configurarse de la siguiente manera:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefijo para claves Redis
        'connection' => 'default', // Conexión Redis desde database.php
        'ttl' => 100,              // TTL del mensaje en segundos
    ],
],
```

## Variables de Entorno

El paquete soporta las siguientes variables de entorno para permitir la configuración sin modificar los archivos de configuración:

| Variable | Descripción | Valor Predeterminado |
|----------|-------------|---------|
| `MCP_SERVER_ENABLED` | Habilitar o deshabilitar el servidor MCP | `true` |
| `MCP_REDIS_CONNECTION` | Nombre de conexión Redis desde database.php | `default` |

### Ejemplo de Configuración .env

```
# Deshabilitar el servidor MCP en entornos específicos
MCP_SERVER_ENABLED=false

# Usar una conexión Redis específica para MCP
MCP_REDIS_CONNECTION=mcp
```

## Licencia

Este proyecto se distribuye bajo la licencia MIT.
