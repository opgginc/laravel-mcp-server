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

## ⚠️ Критические изменения в v1.1.0

Версия 1.1.0 внесла значительные и критические изменения в `ToolInterface`. Если вы обновляетесь с v1.0.x, вы **обязательно** должны обновить ваши реализации инструментов в соответствии с новым интерфейсом.

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

1.  **Найти старые инструменты:** Ищет классы, реализующие `ToolInterface` со старыми сигнатурами методов.
2.  **Создать резервные копии:** Перед внесением изменений создаст резервную копию вашего оригинального файла инструмента с расширением `.backup` (например, `YourTool.php.backup`). Если файл резервной копии уже существует, оригинальный файл будет пропущен во избежание случайной потери данных.
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

Команда будет выводить свой прогресс, указывая, какие файлы обрабатываются, резервируются и мигрируются. Всегда проверяйте изменения, внесенные инструментом. Хотя он стремится быть точным, сложные или необычно отформатированные файлы инструментов могут потребовать ручных корректировок.

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
    // Добавляем новый метод messageType()
    public function messageType(): ProcessMessageType
    {
        // Возвращаем подходящий тип сообщения, например, для стандартного инструмента
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // Переименован
    public function description(): string { return 'This is my new tool.'; } // Переименован
    public function inputSchema(): array { return []; } // Переименован
    public function annotations(): array { return []; } // Переименован
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Обзор Laravel MCP Server

Laravel MCP Server — это мощный пакет, разработанный для упрощения реализации серверов Model Context Protocol (MCP) в Laravel приложениях. **В отличие от большинства Laravel MCP пакетов, которые используют транспорт Standard Input/Output (stdio)**, этот пакет фокусируется на **Streamable HTTP** транспорте и по-прежнему включает **legacy SSE провайдер** для обратной совместимости, обеспечивая безопасный и контролируемый метод интеграции.

### Почему Streamable HTTP вместо STDIO?

Хотя stdio прост и широко используется в MCP реализациях, он имеет значительные последствия для безопасности в корпоративных средах:

- **Риск безопасности**: STDIO транспорт потенциально раскрывает детали внутренней системы и спецификации API
- **Защита данных**: Организации должны защищать проприетарные API endpoints и архитектуру внутренних систем
- **Контроль**: Streamable HTTP предлагает лучший контроль над каналом связи между LLM клиентами и вашим приложением

Реализуя MCP сервер с Streamable HTTP транспортом, предприятия могут:

- Раскрывать только необходимые инструменты и ресурсы, сохраняя детали проприетарных API в тайне
- Поддерживать контроль над процессами аутентификации и авторизации

Ключевые преимущества:

- Бесшовная и быстрая реализация Streamable HTTP в существующих Laravel проектах
- Поддержка последних версий Laravel и PHP
- Эффективная серверная коммуникация и обработка данных в реальном времени
- Повышенная безопасность для корпоративных сред

## Ключевые особенности

- Поддержка коммуникации в реальном времени через Streamable HTTP с SSE интеграцией
- Реализация инструментов и ресурсов, соответствующих спецификациям Model Context Protocol
- Архитектура на основе адаптеров с паттерном Pub/Sub сообщений (начиная с Redis, планируется больше адаптеров)
- Простая конфигурация роутинга и middleware

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
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider" --no-interaction
   ```

## Базовое использование

### Создание и добавление пользовательских инструментов

Пакет предоставляет удобные Artisan команды для генерации новых инструментов:

```bash
php artisan make:mcp-tool MyCustomTool
```

Эта команда:

- Обрабатывает различные форматы ввода (пробелы, дефисы, смешанный регистр)
- Автоматически преобразует имя в правильный формат
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
    // Определяет, как обрабатываются сообщения инструмента, часто связано с транспортом.
    public function messageType(): ProcessMessageType;

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

**`messageType(): ProcessMessageType`**

Этот метод указывает тип обработки сообщений для вашего инструмента. Он возвращает значение enum `ProcessMessageType`. Доступные типы:

- `ProcessMessageType::HTTP`: Для инструментов, взаимодействующих через стандартный HTTP запрос/ответ. Наиболее распространенный для новых инструментов.
- `ProcessMessageType::SSE`: Для инструментов, специально разработанных для работы с Server-Sent Events.

Для большинства инструментов, особенно тех, которые разработаны для основного провайдера `streamable_http`, вы будете возвращать `ProcessMessageType::HTTP`.

**`name(): string`**

Это идентификатор для вашего инструмента. Он должен быть уникальным. Клиенты будут использовать это имя для запроса вашего инструмента. Например: `get-weather`, `calculate-sum`.

**`description(): string`**

Четкое, краткое описание функциональности вашего инструмента. Это используется в документации, и UI MCP клиентов (например, MCP Inspector) могут отображать его пользователям.

**`inputSchema(): array`**

Этот метод критически важен для определения ожидаемых входных параметров вашего инструмента. Он должен возвращать массив, который следует структуре, похожей на JSON Schema. Эта схема используется:

- Клиентами для понимания, какие данные отправлять.
- Потенциально сервером или клиентом для валидации входных данных.
- Инструментами вроде MCP Inspector для генерации форм для тестирования.

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
// Продолжаем с валидированными $arguments['userId'] и $arguments['includeDetails']
```

**`annotations(): array`**

Этот метод предоставляет метаданные о поведении и характеристиках вашего инструмента, следуя официальной [спецификации MCP Tool Annotations](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Аннотации помогают MCP клиентам категоризировать инструменты, принимать обоснованные решения об одобрении инструментов и предоставлять подходящие пользовательские интерфейсы.

**Стандартные MCP аннотации:**

Model Context Protocol определяет несколько стандартных аннотаций, которые понимают клиенты:

- **`title`** (string): Человекочитаемый заголовок для инструмента, отображаемый в UI клиентов
- **`readOnlyHint`** (boolean): Указывает, только ли читает данные инструмент без изменения окружения (по умолчанию: false)
- **`destructiveHint`** (boolean): Предполагает, может ли инструмент выполнять деструктивные операции, такие как удаление данных (по умолчанию: true)
- **`idempotentHint`** (boolean): Указывает, не имеют ли повторные вызовы с теми же аргументами дополнительного эффекта (по умолчанию: false)
- **`openWorldHint`** (boolean): Сигнализирует, взаимодействует ли инструмент с внешними сущностями за пределами локального окружения (по умолчанию: true)

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

### Тестирование MCP инструментов

Пакет включает специальную команду для тестирования ваших MCP инструментов без необходимости в реальном MCP клиенте:

```bash
# Интерактивное тестирование конкретного инструмента
php artisan mcp:test-tool MyCustomTool

# Список всех доступных инструментов
php artisan mcp:test-tool --list

# Тестирование с конкретным JSON входом
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

Это помогает вам быстро разрабатывать и отлаживать инструменты:

- Показывает схему входных данных инструмента и валидирует входы
- Выполняет инструмент с предоставленными вами входными данными
- Отображает отформатированные результаты или подробную информацию об ошибках
- Поддерживает сложные типы входных данных, включая объекты и массивы

### Визуализация MCP инструментов с Inspector

Вы также можете использовать Model Context Protocol Inspector для визуализации и тестирования ваших MCP инструментов:

```bash
# Запуск MCP Inspector без установки
npx @modelcontextprotocol/inspector node build/index.js
```

Это обычно откроет веб-интерфейс на `localhost:6274`. Для тестирования вашего MCP сервера:

1. **Предупреждение**: `php artisan serve` НЕ МОЖЕТ использоваться с этим пакетом, потому что он не может обрабатывать несколько PHP соединений одновременно. Поскольку MCP SSE требует обработки нескольких соединений одновременно, вы должны использовать одну из этих альтернатив:

   - **Laravel Octane** (Самый простой вариант):

     ```bash
     # Установка и настройка Laravel Octane с FrankenPHP (рекомендуется)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Запуск Octane сервера
     php artisan octane:start
     ```

     > **Важно**: При установке Laravel Octane убедитесь, что используете FrankenPHP в качестве сервера. Пакет может работать неправильно с RoadRunner из-за проблем совместимости с SSE соединениями. Если вы можете помочь исправить эту проблему совместимости с RoadRunner, пожалуйста, отправьте Pull Request — ваш вклад будет очень ценен!

     Подробности смотрите в [документации Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Production-grade варианты**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Пользовательская Docker настройка

   * Любой веб-сервер, который правильно поддерживает SSE streaming (требуется только для legacy SSE провайдера)

2. В интерфейсе Inspector введите URL MCP endpoint вашего Laravel сервера (например, `http://localhost:8000/mcp`). Если вы используете legacy SSE провайдер, используйте вместо этого SSE URL (`http://localhost:8000/mcp/sse`).
3. Подключитесь и визуально исследуйте доступные инструменты

MCP endpoint следует паттерну: `http://[ваш-laravel-сервер]/[default_path]` где `default_path` определен в вашем файле `config/mcp-server.php`.

## Продвинутые возможности

### Архитектура Pub/Sub с SSE адаптерами (legacy провайдер)

Пакет реализует паттерн publish/subscribe (pub/sub) сообщений через свою систему адаптеров:

1. **Publisher (Сервер)**: Когда клиенты отправляют запросы на endpoint `/message`, сервер обрабатывает эти запросы и публикует ответы через настроенный адаптер.

2. **Message Broker (Адаптер)**: Адаптер (например, Redis) поддерживает очереди сообщений для каждого клиента, идентифицируемые уникальными ID клиентов. Это обеспечивает надежный асинхронный слой коммуникации.

3. **Subscriber (SSE соединение)**: Долгоживущие SSE соединения подписываются на сообщения для соответствующих клиентов и доставляют их в реальном времени. Это применяется только при использовании legacy SSE провайдера.

Эта архитектура обеспечивает:

- Масштабируемую коммуникацию в реальном времени
- Надежную доставку сообщений даже во время временных отключений
- Эффективную обработку нескольких одновременных клиентских соединений
- Потенциал для распределенных серверных развертываний

### Конфигурация Redis адаптера

Адаптер Redis по умолчанию может быть настроен следующим образом:

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

## Переменные окружения

Пакет поддерживает следующие переменные окружения для возможности конфигурации без изменения конфигурационных файлов:

| Переменная             | Описание                                     | По умолчанию |
| ---------------------- | -------------------------------------------- | ------------ |
| `MCP_SERVER_ENABLED`   | Включить или отключить MCP сервер            | `true`       |

### Пример конфигурации .env

```
# Отключить MCP сервер в определенных окружениях
MCP_SERVER_ENABLED=false
```

## Перевод README.md

Для перевода этого README на другие языки с использованием Claude API (параллельная обработка):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Вы также можете переводить конкретные языки:

```bash
python scripts/translate_readme.py es ko
```

## Лицензия

Этот проект распространяется под лицензией MIT.
