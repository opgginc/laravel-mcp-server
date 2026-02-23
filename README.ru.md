<h1 align="center">Laravel MCP Server от OP.GG</h1>

<p align="center">
  Мощный Laravel пакет для бесшовного создания Model Context Protocol сервера
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Официальный сайт</a>
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

## ⚠️ Информация о версиях и критические изменения

### Изменения в v2.0.0 (Текущая версия) ✅

Версия 2.0.0 переводит пакет на route-first архитектуру и удаляет legacy пути транспорта/конфигурации:

- **Явная регистрация endpoint-ов**: используйте `Route::mcp('/mcp')` в Laravel и `McpRoute::register('/mcp')` в Lumen.
- **Только Streamable HTTP**: legacy SSE endpoint-ы/адаптеры удалены.
- **Удален bootstrap через config**: `config/mcp-server.php` и авто-регистрация маршрутов больше не используются.
- **Удалены legacy транспортные методы инструментов**: `messageType()` удален, `isStreaming()` больше не используется в runtime.
- **Поиск инструментов через маршруты**: `mcp:test-tool` теперь читает инструменты из зарегистрированных MCP endpoint-ов.

Подробности: [Руководство по миграции v2.0.0](docs/migrations/v2.0.0-migration.md).

### Критические изменения в v1.1.0

Версия 1.1.0 внесла значительные и критические изменения в `ToolInterface`. Если вы обновляетесь с v1.0.x, вы **должны** обновить реализации ваших инструментов в соответствии с новым интерфейсом.

**Ключевые изменения в `ToolInterface`:**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` был обновлен следующим образом:

1.  **Добавлен новый метод:**

    - `messageType(): ProcessMessageType`
      - Этот метод критически важен для новой поддержки HTTP stream и определяет тип обрабатываемого сообщения.

2.  **Переименование методов:**
    - `getName()` теперь `name()`
    - `getDescription()` теперь `description()`
    - `getInputSchema()` теперь `inputSchema()`
    - `getAnnotations()` теперь `annotations()`

**Как обновить ваши инструменты:**

### Автоматическая миграция инструментов для v1.1.0

Чтобы помочь с переходом на новый `ToolInterface`, введенный в v1.1.0, мы включили Artisan команду, которая может помочь автоматизировать рефакторинг ваших существующих инструментов:

```bash
php artisan mcp:migrate-tools {path?}
```

**Что она делает:**

Эта команда просканирует PHP файлы в указанной директории (по умолчанию `app/MCP/Tools/`) и попытается:

1.  **Найти старые инструменты:** Она ищет классы, реализующие `ToolInterface` со старыми сигнатурами методов.
2.  **Создать резервные копии:** Перед внесением изменений она создаст резервную копию вашего оригинального файла инструмента с расширением `.backup` (например, `YourTool.php.backup`). Если файл резервной копии уже существует, оригинальный файл будет пропущен для предотвращения случайной потери данных.
3.  **Рефакторить инструмент:**
    - Переименовать методы:
      - `getName()` в `name()`
      - `getDescription()` в `description()`
      - `getInputSchema()` в `inputSchema()`
      - `getAnnotations()` в `annotations()`
    - Добавить новый метод `messageType()`, который по умолчанию будет возвращать `ProcessMessageType::SSE`.
    - Убедиться, что присутствует выражение `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;`.

**Использование:**

После обновления пакета `opgginc/laravel-mcp-server` до v1.1.0 или выше, если у вас есть существующие инструменты, написанные для v1.0.x, настоятельно рекомендуется запустить эту команду:

```bash
php artisan mcp:migrate-tools
```

Если ваши инструменты находятся в директории, отличной от `app/MCP/Tools/`, вы можете указать путь:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

Команда будет выводить свой прогресс, указывая, какие файлы обрабатываются, резервируются и мигрируются. Всегда проверяйте изменения, внесенные инструментом. Хотя он стремится быть точным, сложные или необычно отформатированные файлы инструментов могут потребовать ручной настройки.

Этот инструмент должен значительно облегчить процесс миграции и помочь вам быстро адаптироваться к новой структуре интерфейса.

### Ручная миграция

Если вы предпочитаете мигрировать ваши инструменты вручную, вот сравнение, которое поможет вам адаптировать существующие инструменты:

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

**`ToolInterface` v1.1.0 (Новый):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // Новый метод
    public function name(): string;                     // Переименован
    public function description(): string;              // Переименован
    public function inputSchema(): array;               // Переименован
    public function annotations(): array;               // Переименован
    public function execute(array $arguments): mixed;   // Без изменений
}
```

**Пример обновленного инструмента:**

Если ваш инструмент v1.0.x выглядел так:

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

Вам нужно обновить его для v1.1.0 следующим образом:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // Импортируем enum

class MyNewTool implements ToolInterface
{
    /**
     * @deprecated since v1.3.0, use isStreaming() instead. Will be removed in v2.0.0
     */
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function isStreaming(): bool
    {
        return false; // Большинство инструментов должны возвращать false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Обзор Laravel MCP Server

Laravel MCP Server — это мощный пакет, разработанный для упрощения реализации серверов Model Context Protocol (MCP) в Laravel приложениях. **В отличие от большинства Laravel MCP пакетов, которые используют транспорт Standard Input/Output (stdio)**, этот пакет фокусируется на **Streamable HTTP** транспорте и по-прежнему включает **legacy SSE провайдер** для обратной совместимости, обеспечивая безопасный и контролируемый метод интеграции.

### Почему Streamable HTTP вместо STDIO?

Хотя stdio прост и широко используется в MCP реализациях, он имеет значительные последствия для безопасности в корпоративных средах:

- **Риск безопасности**: STDIO транспорт потенциально раскрывает внутренние детали системы и спецификации API
- **Защита данных**: Организации должны защищать проприетарные API endpoints и внутреннюю архитектуру системы
- **Контроль**: Streamable HTTP предлагает лучший контроль над каналом связи между LLM клиентами и вашим приложением

Реализуя MCP сервер с Streamable HTTP транспортом, предприятия могут:

- Раскрывать только необходимые инструменты и ресурсы, сохраняя детали проприетарных API в тайне
- Поддерживать контроль над процессами аутентификации и авторизации

Ключевые преимущества:

- Бесшовная и быстрая реализация Streamable HTTP в существующих Laravel проектах
- Поддержка последних версий Laravel и PHP
- Эффективная серверная коммуникация и обработка данных в реальном времени
- Повышенная безопасность для корпоративных сред

## Ключевые возможности

- Поддержка коммуникации в реальном времени через Streamable HTTP с SSE интеграцией
- Реализация инструментов и ресурсов, соответствующих спецификациям Model Context Protocol
- Архитектура на основе адаптеров с паттерном Pub/Sub сообщений (начиная с Redis, планируется больше адаптеров)
- Простая настройка маршрутизации и middleware

### Провайдеры транспорта

Опция конфигурации `server_provider` контролирует, какой транспорт используется. Доступные провайдеры:

1. **streamable_http** — рекомендуемый по умолчанию. Использует стандартные HTTP запросы и избегает проблем с платформами, которые закрывают SSE соединения примерно через минуту (например, многие serverless среды).
2. **sse** — legacy провайдер, сохраненный для обратной совместимости. Он полагается на долгоживущие SSE соединения и может не работать на платформах с короткими HTTP таймаутами.

Протокол MCP также определяет режим "Streamable HTTP SSE", но этот пакет его не реализует и не планирует этого делать.

## Требования

- PHP >=8.2
- Laravel >=10.x

## Установка

1. Установите пакет через Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Опубликуйте конфигурационный файл:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Базовое использование


### Создание и добавление пользовательских инструментов

Пакет предоставляет удобные Artisan команды для генерации новых инструментов:

```bash
php artisan make:mcp-tool MyCustomTool
```

Эта команда:

- Обрабатывает различные форматы ввода (пробелы, дефисы, смешанный регистр)
- Автоматически конвертирует имя в правильный формат
- Создает правильно структурированный класс инструмента в `app/MCP/Tools`
- Предлагает автоматически зарегистрировать инструмент в вашей конфигурации

Вы также можете вручную создавать и регистрировать инструменты в `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Реализация инструмента
}
```

### Понимание структуры вашего инструмента (ToolInterface)

Когда вы создаете инструмент, реализуя `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, вам нужно определить несколько методов. Вот разбор каждого метода и его назначения:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    /**
     * @deprecated since v1.3.0, use isStreaming() instead. Will be removed in v2.0.0
     */
    public function messageType(): ProcessMessageType;

    // НОВОЕ в v1.3.0: Определяет, требует ли этот инструмент streaming (SSE) вместо стандартного HTTP.
    public function isStreaming(): bool;

    // Уникальное, вызываемое имя вашего инструмента (например, 'get-user-details').
    public function name(): string;

    // Человекочитаемое описание того, что делает ваш инструмент.
    public function description(): string;

    // Определяет ожидаемые входные параметры для вашего инструмента, используя JSON Schema-подобную структуру.
    public function inputSchema(): array;

    // Предоставляет способ добавления произвольных метаданных или аннотаций к вашему инструменту.
    public function annotations(): array;

    // Основная логика вашего инструмента. Получает валидированные аргументы и возвращает результат.
    public function execute(array $arguments): mixed;
}
```

Давайте углубимся в некоторые из этих методов:

**`messageType(): ProcessMessageType` (Устарел в v1.3.0)**

⚠️ **Этот метод устарел с v1.3.0.** Используйте `isStreaming(): bool` вместо него для большей ясности.

Этот метод указывает тип обработки сообщений для вашего инструмента. Он возвращает значение enum `ProcessMessageType`. Доступные типы:

- `ProcessMessageType::HTTP`: Для инструментов, взаимодействующих через стандартный HTTP запрос/ответ. Наиболее распространен для новых инструментов.
- `ProcessMessageType::SSE`: Для инструментов, специально разработанных для работы с Server-Sent Events.

Для большинства инструментов, особенно тех, которые разработаны для основного провайдера `streamable_http`, вы будете возвращать `ProcessMessageType::HTTP`.

**`isStreaming(): bool` (Новое в v1.3.0)**

Это новый, более интуитивный метод для контроля паттернов коммуникации:

- `return false`: Использовать стандартный HTTP запрос/ответ (рекомендуется для большинства инструментов)
- `return true`: Использовать Server-Sent Events для streaming в реальном времени

Большинство инструментов должны возвращать `false`, если вам специально не нужны возможности streaming в реальном времени, такие как:
- Обновления прогресса в реальном времени для долго выполняющихся операций
- Живые потоки данных или инструменты мониторинга
- Интерактивные инструменты, требующие двунаправленной коммуникации

**`name(): string`**

Это идентификатор для вашего инструмента. Он должен быть уникальным. Клиенты будут использовать это имя для запроса вашего инструмента. Например: `get-weather`, `calculate-sum`.

**`description(): string`**

Четкое, краткое описание функциональности вашего инструмента. Это используется в документации, и MCP клиентские UI (как MCP Inspector) могут отображать его пользователям.

**`inputSchema(): array`**

Этот метод критически важен для определения ожидаемых входных параметров вашего инструмента. Он должен возвращать массив, который следует структуре, похожей на JSON Schema. Эта схема используется:

- Клиентами для понимания, какие данные отправлять.
- Потенциально сервером или клиентом для валидации входных данных.
- Инструментами, такими как MCP Inspector, для генерации форм для тестирования.

**Пример `inputSchema()`:**

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
                'default' => false, // Вы можете указать значения по умолчанию
            ],
        ],
        'required' => ['userId'], // Указывает, какие свойства обязательны
    ];
}
```

В вашем методе `execute` вы можете затем валидировать входящие аргументы. Пример `HelloWorldTool` использует `Illuminate\Support\Facades\Validator` для этого:

```php
// Внутри вашего метода execute():
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
// Продолжайте с валидированными $arguments['userId'] и $arguments['includeDetails']
```

**`annotations(): array`**

Этот метод предоставляет метаданные о поведении и характеристиках вашего инструмента, следуя официальной [спецификации MCP Tool Annotations](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Аннотации помогают MCP клиентам категоризировать инструменты, принимать обоснованные решения об одобрении инструментов и предоставлять соответствующие пользовательские интерфейсы.

**Стандартные MCP аннотации:**

Model Context Protocol определяет несколько стандартных аннотаций, которые понимают клиенты:

- **`title`** (string): Человекочитаемый заголовок для инструмента, отображаемый в клиентских UI
- **`readOnlyHint`** (boolean): Указывает, читает ли инструмент только данные без изменения среды (по умолчанию: false)
- **`destructiveHint`** (boolean): Предполагает, может ли инструмент выполнять деструктивные операции, такие как удаление данных (по умолчанию: true)
- **`idempotentHint`** (boolean): Указывает, не имеют ли повторные вызовы с одинаковыми аргументами дополнительного эффекта (по умолчанию: false)
- **`openWorldHint`** (boolean): Сигнализирует, взаимодействует ли инструмент с внешними сущностями за пределами локальной среды (по умолчанию: true)

**Важно:** Это подсказки, а не гарантии. Они помогают клиентам предоставлять лучший пользовательский опыт, но не должны использоваться для критически важных для безопасности решений.

**Пример со стандартными MCP аннотациями:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // Инструмент только читает пользовательские данные
        'destructiveHint' => false,    // Инструмент не удаляет и не изменяет данные
        'idempotentHint' => true,      // Безопасно вызывать несколько раз
        'openWorldHint' => false,      // Инструмент обращается только к локальной базе данных
    ];
}
```

**Реальные примеры по типам инструментов:**

```php
// Инструмент запроса к базе данных
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

// Инструмент удаления постов
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Может удалять посты
        'idempotentHint' => false,     // Удаление дважды имеет разные эффекты
        'openWorldHint' => false,
    ];
}

// Инструмент интеграции с API
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Обращается к внешнему weather API
    ];
}
```

**Пользовательские аннотации** также могут быть добавлены для ваших специфических потребностей приложения:

```php
public function annotations(): array
{
    return [
        // Стандартные MCP аннотации
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // Пользовательские аннотации для вашего приложения
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Работа с ресурсами

Ресурсы предоставляют данные с вашего сервера, которые могут быть прочитаны MCP клиентами. Они **контролируются приложением**, что означает, что клиент решает, когда и как их использовать. Создавайте конкретные ресурсы или URI шаблоны в `app/MCP/Resources` и `app/MCP/ResourceTemplates`, используя Artisan помощники:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

Зарегистрируйте сгенерированные классы в `config/mcp-server.php` под массивами `resources` и `resource_templates`. Каждый класс ресурса расширяет базовый класс `Resource` и реализует метод `read()`, который возвращает содержимое `text` или `blob`. Шаблоны расширяют `ResourceTemplate` и описывают динамические URI паттерны, которые клиенты могут использовать. Ресурс идентифицируется URI, таким как `file:///logs/app.log`, и может опционально определять метаданные, такие как `mimeType` или `size`.

**Шаблоны ресурсов с динамическим листингом**: Шаблоны могут опционально реализовать метод `list()` для предоставления конкретных экземпляров ресурсов, которые соответствуют паттерну шаблона. Это позволяет клиентам динамически обнаруживать доступные ресурсы. Метод `list()` позволяет экземплярам ResourceTemplate генерировать список конкретных ресурсов, которые могут быть прочитаны через метод `read()` шаблона.

Перечислите доступные ресурсы, используя endpoint `resources/list`, и читайте их содержимое с помощью `resources/read`. Endpoint `resources/list` возвращает массив конкретных ресурсов, включая как статические ресурсы, так и динамически сгенерированные ресурсы из шаблонов, которые реализуют метод `list()`:

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

**Динамическое чтение ресурсов**: Шаблоны ресурсов поддерживают паттерны URI шаблонов (RFC 6570), которые позволяют клиентам конструировать динамические идентификаторы ресурсов. Когда клиент запрашивает URI ресурса, который соответствует паттерну шаблона, вызывается метод `read()` шаблона с извлеченными параметрами для генерации содержимого ресурса.

Пример рабочего процесса:
1. Шаблон определяет паттерн: `"database://users/{userId}/profile"`
2. Клиент запрашивает: `"database://users/123/profile"`
3. Шаблон извлекает `{userId: "123"}` и вызывает метод `read()`
4. Шаблон возвращает данные профиля пользователя для пользователя с ID 123

Вы также можете перечислить шаблоны отдельно, используя endpoint `resources/templates/list`:

```bash
# Перечислить только шаблоны ресурсов
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

При запуске вашего Laravel MCP сервера удаленно HTTP транспорт работает со стандартными JSON-RPC запросами. Вот простой пример использования `curl` для перечисления и чтения ресурсов:

```bash
# Перечислить ресурсы
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# Прочитать конкретный ресурс
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

Сервер отвечает JSON сообщениями, потоковыми через HTTP соединение, поэтому `curl --no-buffer` может быть использован, если вы хотите видеть инкрементальный вывод.

### Работа с промптами

Промпты предоставляют переиспользуемые текстовые фрагменты с поддержкой аргументов, которые ваши инструменты или пользователи могут запрашивать. Создавайте классы промптов в `app/MCP/Prompts`, используя:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

Зарегистрируйте их в `config/mcp-server.php` под `prompts`. Каждый класс промпта расширяет базовый класс `Prompt` и определяет:
- `name`: Уникальный идентификатор (например, "welcome-user")
- `description`: Опциональное человекочитаемое описание
- `arguments`: Массив определений аргументов с полями name, description и required
- `text`: Шаблон промпта с заполнителями, такими как `{username}`

Перечислите промпты через endpoint `prompts/list` и получайте их, используя `prompts/get` с аргументами:

```bash
# Получить приветственный промпт с аргументами
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### MCP промпты

При создании промптов, которые ссылаются на ваши инструменты или ресурсы, обратитесь к [официальным рекомендациям по промптам](https://modelcontextprotocol.io/docs/concepts/prompts). Промпты — это переиспользуемые шаблоны, которые могут принимать аргументы, включать контекст ресурсов и даже описывать многошаговые рабочие процессы.

**Структура промпта**

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

Клиенты обнаруживают промпты через `prompts/list` и запрашивают конкретные с помощью `prompts/get`:

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

**Пример класса промпта**

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

Промпты могут встраивать ресурсы и возвращать последовательности сообщений для направления LLM. См. официальную документацию для продвинутых примеров и лучших практик.

### Работа с уведомлениями

Уведомления - это fire-and-forget сообщения от MCP клиентов, которые всегда возвращают HTTP 202 Accepted без тела ответа. Они идеально подходят для логирования, отслеживания прогресса, обработки событий и запуска фоновых процессов без блокировки клиента.

#### Создание обработчиков уведомлений

**Базовое использование команды:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**Продвинутые возможности команды:**

```bash
# Интерактивный режим - запрашивает метод, если не указан
php artisan make:mcp-notification MyHandler

# Автоматическая обработка префикса метода
php artisan make:mcp-notification StatusHandler --method=status  # становится notifications/status

# Нормализация имени класса 
php artisan make:mcp-notification "user activity"  # становится UserActivityHandler
```

Команда предоставляет:
- **Интерактивный запрос метода** когда `--method` не указан
- **Автоматическое руководство по регистрации** с готовым для копирования кодом
- **Встроенные примеры тестов** с curl командами 
- **Всеобъемлющие инструкции по использованию** и общие случаи использования

#### Архитектура обработчика уведомлений

Каждый обработчик уведомлений должен реализовывать абстрактный класс `NotificationHandler`:

```php
abstract class NotificationHandler
{
    // Обязательно: Тип сообщения (обычно ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // Обязательно: Метод уведомления для обработки  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // Обязательно: Выполнение логики уведомления
    abstract public function execute(?array $params = null): void;
}
```

**Ключевые архитектурные компоненты:**

- **`MESSAGE_TYPE`**: Обычно `ProcessMessageType::HTTP` для стандартных уведомлений
- **`HANDLE_METHOD`**: JSON-RPC метод, который обрабатывает этот обработчик (должен начинаться с `notifications/`)
- **`execute()`**: Содержит вашу логику уведомлений - возвращает void (ответ не отправляется)
- **Валидация конструктора**: Автоматически валидирует, что обязательные константы определены

#### Встроенные обработчики уведомлений

Пакет включает четыре предварительно созданных обработчика для общих MCP сценариев:

**1. InitializedHandler (`notifications/initialized`)**
- **Цель**: Обрабатывает подтверждения инициализации клиента после успешного handshake
- **Параметры**: Информация о клиенте и возможности
- **Использование**: Отслеживание сессий, логирование клиента, события инициализации

**2. ProgressHandler (`notifications/progress`)**
- **Цель**: Обрабатывает обновления прогресса для долгосрочных операций
- **Параметры**: 
  - `progressToken` (string): Уникальный идентификатор операции
  - `progress` (number): Текущее значение прогресса
  - `total` (number, опционально): Общее значение прогресса для расчета процентов
- **Использование**: Отслеживание прогресса в реальном времени, мониторинг загрузок, завершение задач

**3. CancelledHandler (`notifications/cancelled`)**
- **Цель**: Обрабатывает уведомления об отмене запросов
- **Параметры**:
  - `requestId` (string): ID запроса для отмены
  - `reason` (string, опционально): Причина отмены
- **Использование**: Завершение фоновых задач, очистка ресурсов, прерывание операций

**4. MessageHandler (`notifications/message`)**
- **Цель**: Обрабатывает общие сообщения логирования и коммуникации
- **Параметры**:
  - `level` (string): Уровень лога (info, warning, error, debug)
  - `message` (string): Содержимое сообщения
  - `logger` (string, опционально): Имя логгера
- **Использование**: Логирование на стороне клиента, отладка, общая коммуникация

#### Примеры обработчиков для общих сценариев

```php
// Отслеживание прогресса загрузки файлов
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
            
            // Трансляция обновления в реальном времени
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// Активность пользователя и логирование аудита
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
        
        // Запуск уведомлений о безопасности для чувствительных действий
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// Запуск фоновых задач
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

#### Регистрация обработчиков уведомлений

**В вашем провайдере сервисов:**

```php
// В AppServiceProvider или выделенном MCP провайдере сервисов
public function boot()
{
    $server = app(MCPServer::class);
    
    // Регистрация встроенных обработчиков (опционально - регистрируются по умолчанию)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // Регистрация пользовательских обработчиков
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### Тестирование уведомлений

**Использование curl для тестирования обработчиков уведомлений:**

```bash
# Тестирование уведомления о прогрессе
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
# Ожидается: HTTP 202 с пустым телом

# Тестирование уведомления об активности пользователя  
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
# Ожидается: HTTP 202 с пустым телом

# Тестирование уведомления об отмене
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
# Ожидается: HTTP 202 с пустым телом
```

**Ключевые заметки о тестировании:**
- Уведомления возвращают **HTTP 202** (никогда 200)
- Тело ответа **всегда пустое**
- JSON-RPC ответное сообщение не отправляется
- Проверьте логи сервера для подтверждения обработки уведомлений

#### Обработка ошибок и валидация

**Общие паттерны валидации:**

```php
public function execute(?array $params = null): void
{
    // Валидация обязательных параметров
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // Не бросайте исключение - уведомления должны быть отказоустойчивыми
    }
    
    // Валидация типов параметров
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // Безопасная экстракция параметров со значениями по умолчанию
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // Обработка уведомления...
}
```

**Лучшие практики обработки ошибок:**
- **Логировать ошибки** вместо выбрасывания исключений
- **Использовать защитное программирование** с null проверками и значениями по умолчанию
- **Изящное падение** - не ломать рабочий процесс клиента
- **Валидировать входы** но продолжать обработку когда возможно
- **Мониторить уведомления** через логирование и метрики

### Тестирование MCP инструментов

Пакет включает специальную команду для тестирования ваших MCP инструментов без необходимости в реальном MCP клиенте:

```bash
# Интерактивно тестировать конкретный инструмент
php artisan mcp:test-tool MyCustomTool

# Перечислить все доступные инструменты
php artisan mcp:test-tool --list

# Тестировать с конкретным JSON входом
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

Это помогает вам быстро разрабатывать и отлаживать инструменты:

- Показывая входную схему инструмента и валидируя входы
- Выполняя инструмент с вашим предоставленным входом
- Отображая отформатированные результаты или подробную информацию об ошибках
- Поддерживая сложные типы входов, включая объекты и массивы

### Визуализация MCP инструментов с Inspector

Вы также можете использовать Model Context Protocol Inspector для визуализации и тестирования ваших MCP инструментов:

```bash
# Запустить MCP Inspector без установки
npx @modelcontextprotocol/inspector node build/index.js
```

Это обычно откроет веб-интерфейс на `localhost:6274`. Для тестирования вашего MCP сервера:

1. **Предупреждение**: `php artisan serve` НЕ МОЖЕТ быть использован с этим пакетом, потому что он не может обрабатывать несколько PHP соединений одновременно. Поскольку MCP SSE требует обработки нескольких соединений одновременно, вы должны использовать одну из этих альтернатив:

   - **Laravel Octane** (Самый простой вариант):

     ```bash
     # Установить и настроить Laravel Octane с FrankenPHP (рекомендуется)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Запустить Octane сервер
     php artisan octane:start
     ```

     > **Важно**: При установке Laravel Octane убедитесь, что используете FrankenPHP в качестве сервера. Пакет может работать неправильно с RoadRunner из-за проблем совместимости с SSE соединениями. Если вы можете помочь исправить эту проблему совместимости с RoadRunner, пожалуйста, отправьте Pull Request — ваш вклад будет очень ценен!

     Для деталей см. [документацию Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Варианты production-уровня**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Пользовательская Docker настройка

   * Любой веб-сервер, который правильно поддерживает SSE streaming (требуется только для legacy SSE провайдера)

2. В интерфейсе Inspector введите URL MCP endpoint вашего Laravel сервера (например, `http://localhost:8000/mcp`). Если вы используете legacy SSE провайдер, используйте SSE URL вместо этого (`http://localhost:8000/mcp/sse`).
3. Подключитесь и исследуйте доступные инструменты визуально

MCP endpoint следует паттерну: `http://[your-laravel-server]/[default_path]`, где `default_path` определен в вашем файле `config/mcp-server.php`.

## Продвинутые возможности

### Архитектура Pub/Sub с SSE адаптерами (legacy провайдер)

Пакет реализует паттерн publish/subscribe (pub/sub) сообщений через свою систему адаптеров:

1. **Publisher (Сервер)**: Когда клиенты отправляют запросы на endpoint `/message`, сервер обрабатывает эти запросы и публикует ответы через настроенный адаптер.

2. **Message Broker (Адаптер)**: Адаптер (например, Redis) поддерживает очереди сообщений для каждого клиента, идентифицированного уникальными ID клиентов. Это обеспечивает надежный асинхронный коммуникационный слой.

3. **Subscriber (SSE соединение)**: Долгоживущие SSE соединения подписываются на сообщения для своих соответствующих клиентов и доставляют их в реальном времени. Это применяется только при использовании legacy SSE провайдера.

Эта архитектура обеспечивает:

- Масштабируемую коммуникацию в реальном времени
- Надежную доставку сообщений даже во время временных отключений
- Эффективную обработку нескольких одновременных клиентских соединений
- Потенциал для распределенных серверных развертываний

### Конфигурация Redis адаптера

Redis адаптер по умолчанию может быть настроен следующим образом:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Префикс для Redis ключей
        'connection' => 'default', // Redis соединение из database.php
        'ttl' => 100,              // TTL сообщений в секундах
    ],
],
```

## Перевод README.md

Для перевода этого README на другие языки, используя Claude API (Параллельная обработка):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Вы также можете переводить конкретные языки:

```bash
python scripts/translate_readme.py es ko
```

## Примечания по миграции v2.0.0

Версия 2.0.0 уже доступна. Если вы мигрируете с v1.x, примените следующие изменения.

### Что изменилось в v2.0.0

- `messageType(): ProcessMessageType` удален.
- `isStreaming(): bool` больше не используется в runtime (опциональная очистка).
- `ProcessMessageType::SSE` удален.
- Поддерживается только Streamable HTTP (`/sse` и `/message` удалены).
- Удалены ключи конфигурации MCP (`server_provider`, `sse_adapter`, `adapters`, `enabled`).

### Как мигрировать

- Регистрируйте MCP endpoint-ы напрямую в маршрутах через `Route::mcp(...)` (Laravel) или `McpRoute::register(...)` (Lumen).
- Перенесите server info/tools/resources/templates/prompts из config в цепочку route builder.
- Запустите `php artisan mcp:migrate-tools` для очистки legacy сигнатур инструментов.
- Обновите endpoint-ы MCP клиентов на фактический путь маршрута (например, `/mcp`).
- Полная инструкция: [Руководство по миграции v2.0.0](docs/migrations/v2.0.0-migration.md).

### Проверка после миграции

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
vendor/bin/pest
vendor/bin/phpstan analyse
```

## Лицензия

Этот проект распространяется под лицензией MIT.