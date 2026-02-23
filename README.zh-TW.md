<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  一個強大的 Laravel 套件，讓你無縫建構 Model Context Protocol Server
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">官方網站</a>
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

## ⚠️ 版本資訊與重大變更

### v2.0.0 變更（當前版本）✅

v2.0.0 採用 route-first 架構，並移除舊版傳輸/設定路徑：

- **明確註冊端點**：Laravel 使用 `Route::mcp('/mcp')`，Lumen 使用 `McpRoute::register('/mcp')`。
- **僅支援 Streamable HTTP**：舊版 SSE 端點/適配器已移除。
- **移除 config 驅動啟動方式**：不再使用 `config/mcp-server.php` 與自動路由註冊。
- **移除工具的舊版傳輸方法**：`messageType()` 已移除，`isStreaming()` 執行階段不再使用。
- **以路由為基礎的工具探索**：`mcp:test-tool` 現在從已註冊 MCP 端點探索工具。

完整步驟請參考：[v2.0.0 遷移指南](docs/migrations/v2.0.0-migration.md)。

### v1.1.0 的重大變更

版本 1.1.0 對 `ToolInterface` 引入了重大且破壞性的變更。如果你正在從 v1.0.x 升級，你**必須**更新你的工具實作以符合新的介面。

**`ToolInterface` 的關鍵變更：**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` 已更新如下：

1.  **新增方法：**

    - `messageType(): ProcessMessageType`
      - 這個方法對於新的 HTTP stream 支援至關重要，決定正在處理的訊息類型。

2.  **方法重新命名：**
    - `getName()` 現在是 `name()`
    - `getDescription()` 現在是 `description()`
    - `getInputSchema()` 現在是 `inputSchema()`
    - `getAnnotations()` 現在是 `annotations()`

**如何更新你的工具：**

### v1.1.0 自動化工具遷移

為了協助轉換到 v1.1.0 引入的新 `ToolInterface`，我們提供了一個 Artisan 指令來幫助自動化重構你現有的工具：

```bash
php artisan mcp:migrate-tools {path?}
```

**功能說明：**

這個指令會掃描指定目錄中的 PHP 檔案（預設為 `app/MCP/Tools/`）並嘗試：

1.  **識別舊工具：** 尋找實作舊方法簽名的 `ToolInterface` 類別。
2.  **建立備份：** 在進行任何變更之前，會建立原始工具檔案的備份，副檔名為 `.backup`（例如 `YourTool.php.backup`）。如果備份檔案已存在，原始檔案會被跳過以防止意外資料遺失。
3.  **重構工具：**
    - 重新命名方法：
      - `getName()` 改為 `name()`
      - `getDescription()` 改為 `description()`
      - `getInputSchema()` 改為 `inputSchema()`
      - `getAnnotations()` 改為 `annotations()`
    - 新增 `messageType()` 方法，預設回傳 `ProcessMessageType::SSE`。
    - 確保包含 `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` 陳述式。

**使用方式：**

在將 `opgginc/laravel-mcp-server` 套件更新到 v1.1.0 或更新版本後，如果你有為 v1.0.x 編寫的現有工具，強烈建議執行此指令：

```bash
php artisan mcp:migrate-tools
```

如果你的工具位於 `app/MCP/Tools/` 以外的目錄，你可以指定路徑：

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

指令會輸出進度，顯示正在處理、備份和遷移的檔案。請務必檢查工具所做的變更。雖然它力求準確，但複雜或格式異常的工具檔案可能需要手動調整。

這個工具應該能大幅簡化遷移過程，幫助你快速適應新的介面結構。

### 手動遷移

如果你偏好手動遷移工具，以下是比較說明，幫助你調整現有工具：

**v1.0.x `ToolInterface`：**

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

**v1.1.0 `ToolInterface`（新版）：**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // 新方法
    public function name(): string;                     // 重新命名
    public function description(): string;              // 重新命名
    public function inputSchema(): array;               // 重新命名
    public function annotations(): array;               // 重新命名
    public function execute(array $arguments): mixed;   // 無變更
}
```

**更新後工具的範例：**

如果你的 v1.0.x 工具看起來像這樣：

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

你需要為 v1.1.0 更新如下：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // 匯入 enum

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
        return false; // 大多數工具應該回傳 false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Laravel MCP Server 概述

Laravel MCP Server 是一個強大的套件，專為在 Laravel 應用程式中簡化 Model Context Protocol (MCP) 伺服器的實作而設計。**與大多數使用標準輸入/輸出 (stdio) 傳輸的 Laravel MCP 套件不同**，這個套件專注於 **Streamable HTTP** 傳輸，同時仍包含**舊版 SSE 提供者**以保持向後相容性，提供安全且受控的整合方法。

### 為什麼選擇 Streamable HTTP 而非 STDIO？

雖然 stdio 簡單直接且在 MCP 實作中廣泛使用，但它對企業環境有重大的安全影響：

- **安全風險**：STDIO 傳輸可能暴露內部系統細節和 API 規格
- **資料保護**：組織需要保護專有的 API 端點和內部系統架構
- **控制**：Streamable HTTP 對 LLM 客戶端與你的應用程式之間的通訊通道提供更好的控制

透過使用 Streamable HTTP 傳輸實作 MCP 伺服器，企業可以：

- 只暴露必要的工具和資源，同時保持專有 API 細節的私密性
- 維持對身份驗證和授權流程的控制

主要優勢：

- 在現有 Laravel 專案中無縫且快速實作 Streamable HTTP
- 支援最新的 Laravel 和 PHP 版本
- 高效的伺服器通訊和即時資料處理
- 為企業環境提供增強的安全性

## 主要功能

- 透過 Streamable HTTP 與 SSE 整合支援即時通訊
- 實作符合 Model Context Protocol 規格的工具和資源
- 基於適配器的設計架構，採用 Pub/Sub 訊息模式（從 Redis 開始，計劃更多適配器）
- 簡單的路由和中介軟體配置

### 傳輸提供者

配置選項 `server_provider` 控制使用哪種傳輸。可用的提供者有：

1. **streamable_http** – 推薦的預設選項。使用標準 HTTP 請求，避免在約一分鐘後關閉 SSE 連線的平台問題（例如許多 serverless 環境）。
2. **sse** – 為保持向後相容性而保留的舊版提供者。它依賴長時間的 SSE 連線，在 HTTP 超時時間較短的平台上可能無法運作。

MCP 協定也定義了「Streamable HTTP SSE」模式，但此套件未實作且沒有實作計劃。

## 系統需求

- PHP >=8.2
- Laravel >=10.x

## 安裝

1. 透過 Composer 安裝套件：

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 發布配置檔案：
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 基本使用


### 建立和新增自訂工具

套件提供便利的 Artisan 指令來產生新工具：

```bash
php artisan make:mcp-tool MyCustomTool
```

此指令：

- 處理各種輸入格式（空格、連字號、混合大小寫）
- 自動將名稱轉換為適當的大小寫格式
- 在 `app/MCP/Tools` 中建立結構正確的工具類別
- 提供自動在配置中註冊工具的選項

你也可以在 `config/mcp-server.php` 中手動建立和註冊工具：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 工具實作
}
```

### 了解你的工具結構 (ToolInterface)

當你透過實作 `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` 建立工具時，你需要定義幾個方法。以下是每個方法及其用途的詳細說明：

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

    // v1.3.0 新增：決定此工具是否需要串流 (SSE) 而非標準 HTTP。
    public function isStreaming(): bool;

    // 你的工具的唯一、可呼叫名稱（例如 'get-user-details'）。
    public function name(): string;

    // 你的工具功能的人類可讀描述。
    public function description(): string;

    // 使用類似 JSON Schema 的結構定義你的工具預期的輸入參數。
    public function inputSchema(): array;

    // 提供為你的工具新增任意中繼資料或註解的方式。
    public function annotations(): array;

    // 你的工具的核心邏輯。接收驗證過的參數並回傳結果。
    public function execute(array $arguments): mixed;
}
```

讓我們深入了解其中一些方法：

**`messageType(): ProcessMessageType`（v1.3.0 已棄用）**

⚠️ **此方法自 v1.3.0 起已棄用。** 請改用 `isStreaming(): bool` 以獲得更好的清晰度。

此方法指定你的工具的訊息處理類型。它回傳一個 `ProcessMessageType` enum 值。可用的類型有：

- `ProcessMessageType::HTTP`：用於透過標準 HTTP 請求/回應互動的工具。新工具最常用。
- `ProcessMessageType::SSE`：用於專門設計與 Server-Sent Events 配合使用的工具。

對於大多數工具，特別是為主要 `streamable_http` 提供者設計的工具，你會回傳 `ProcessMessageType::HTTP`。

**`isStreaming(): bool`（v1.3.0 新增）**

這是控制通訊模式的新的、更直觀的方法：

- `return false`：使用標準 HTTP 請求/回應（大多數工具建議使用）
- `return true`：使用 Server-Sent Events 進行即時串流

大多數工具應該回傳 `false`，除非你特別需要即時串流功能，例如：
- 長時間執行操作的即時進度更新
- 即時資料饋送或監控工具
- 需要雙向通訊的互動式工具

**`name(): string`**

這是你的工具的識別符。它應該是唯一的。客戶端會使用這個名稱來請求你的工具。例如：`get-weather`、`calculate-sum`。

**`description(): string`**

你的工具功能的清晰、簡潔描述。這用於文件，MCP 客戶端 UI（如 MCP Inspector）可能會向使用者顯示它。

**`inputSchema(): array`**

此方法對於定義你的工具預期的輸入參數至關重要。它應該回傳一個遵循類似 JSON Schema 結構的陣列。此 schema 用於：

- 讓客戶端了解要傳送什麼資料。
- 可能由伺服器或客戶端用於輸入驗證。
- 讓 MCP Inspector 等工具產生測試表單。

**`inputSchema()` 範例：**

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
                'default' => false, // 你可以指定預設值
            ],
        ],
        'required' => ['userId'], // 指定哪些屬性是必需的
    ];
}
```

在你的 `execute` 方法中，你可以驗證傳入的參數。`HelloWorldTool` 範例使用 `Illuminate\Support\Facades\Validator` 來做這件事：

```php
// 在你的 execute() 方法內：
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
// 使用驗證過的 $arguments['userId'] 和 $arguments['includeDetails'] 繼續處理
```

**`annotations(): array`**

此方法提供關於你的工具行為和特性的中繼資料，遵循官方 [MCP Tool Annotations 規格](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations)。註解幫助 MCP 客戶端分類工具、對工具核准做出明智決策，並提供適當的使用者介面。

**標準 MCP 註解：**

Model Context Protocol 定義了幾個客戶端理解的標準註解：

- **`title`** (string)：工具的人類可讀標題，顯示在客戶端 UI 中
- **`readOnlyHint`** (boolean)：指示工具是否只讀取資料而不修改環境（預設：false）
- **`destructiveHint`** (boolean)：建議工具是否可能執行破壞性操作，如刪除資料（預設：true）
- **`idempotentHint`** (boolean)：指示使用相同參數重複呼叫是否沒有額外效果（預設：false）
- **`openWorldHint`** (boolean)：表示工具是否與本地環境以外的外部實體互動（預設：true）

**重要：** 這些是提示，不是保證。它們幫助客戶端提供更好的使用者體驗，但不應用於安全關鍵決策。

**使用標準 MCP 註解的範例：**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // 工具只讀取使用者資料
        'destructiveHint' => false,    // 工具不刪除或修改資料
        'idempotentHint' => true,      // 多次呼叫是安全的
        'openWorldHint' => false,      // 工具只存取本地資料庫
    ];
}
```

**依工具類型的實際範例：**

```php
// 資料庫查詢工具
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

// 文章刪除工具
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // 可以刪除文章
        'idempotentHint' => false,     // 刪除兩次有不同效果
        'openWorldHint' => false,
    ];
}

// API 整合工具
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // 存取外部天氣 API
    ];
}
```

**自訂註解**也可以為你的特定應用程式需求新增：

```php
public function annotations(): array
{
    return [
        // 標準 MCP 註解
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // 你的應用程式的自訂註解
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### 使用資源

資源從你的伺服器暴露資料，可供 MCP 客戶端讀取。它們是**應用程式控制的**，意味著客戶端決定何時以及如何使用它們。在 `app/MCP/Resources` 和 `app/MCP/ResourceTemplates` 中建立具體資源或 URI 範本，使用 Artisan 輔助工具：

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

在 `config/mcp-server.php` 的 `resources` 和 `resource_templates` 陣列中註冊產生的類別。每個資源類別繼承基礎 `Resource` 類別並實作一個回傳 `text` 或 `blob` 內容的 `read()` 方法。範本繼承 `ResourceTemplate` 並描述客戶端可以使用的動態 URI 模式。資源由 URI 識別，例如 `file:///logs/app.log`，並可選擇性地定義如 `mimeType` 或 `size` 等中繼資料。

**具有動態列表的資源範本**：範本可以選擇性地實作 `list()` 方法，提供符合範本模式的具體資源實例。這讓客戶端能夠動態發現可用資源。`list()` 方法讓 ResourceTemplate 實例能夠產生可透過範本的 `read()` 方法讀取的特定資源清單。

使用 `resources/list` 端點列出可用資源，並使用 `resources/read` 讀取其內容。`resources/list` 端點回傳具體資源陣列，包括靜態資源和從實作 `list()` 方法的範本動態產生的資源：

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

**動態資源讀取**：資源範本支援 URI 範本模式 (RFC 6570)，讓客戶端能夠建構動態資源識別符。當客戶端請求符合範本模式的資源 URI 時，會呼叫範本的 `read()` 方法並傳入提取的參數來產生資源內容。

範例工作流程：
1. 範本定義模式：`"database://users/{userId}/profile"`
2. 客戶端請求：`"database://users/123/profile"`
3. 範本提取 `{userId: "123"}` 並呼叫 `read()` 方法
4. 範本回傳使用者 ID 123 的使用者個人資料資料

你也可以使用 `resources/templates/list` 端點單獨列出範本：

```bash
# 只列出資源範本
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

當遠端執行你的 Laravel MCP 伺服器時，HTTP 傳輸與標準 JSON-RPC 請求配合使用。以下是使用 `curl` 列出和讀取資源的簡單範例：

```bash
# 列出資源
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# 讀取特定資源
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

伺服器透過 HTTP 連線回應串流的 JSON 訊息，所以如果你想看到增量輸出，可以使用 `curl --no-buffer`。

### 使用提示

提示提供可重複使用的文字片段，支援參數，你的工具或使用者可以請求。在 `app/MCP/Prompts` 中建立提示類別：

```bash
php artisan make:mcp-prompt WelcomePrompt
```

在 `config/mcp-server.php` 的 `prompts` 下註冊它們。每個提示類別繼承 `Prompt` 基礎類別並定義：
- `name`：唯一識別符（例如 "welcome-user"）
- `description`：可選的人類可讀描述
- `arguments`：參數定義陣列，包含名稱、描述和必需欄位
- `text`：包含佔位符的提示範本，如 `{username}`

透過 `prompts/list` 端點列出提示，並使用 `prompts/get` 與參數取得它們：

```bash
# 使用參數取得歡迎提示
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### MCP 提示

在製作參考你的工具或資源的提示時，請參考[官方提示指南](https://modelcontextprotocol.io/docs/concepts/prompts)。提示是可重複使用的範本，可以接受參數、包含資源上下文，甚至描述多步驟工作流程。

**提示結構**

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

客戶端透過 `prompts/list` 發現提示，並使用 `prompts/get` 請求特定提示：

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

**提示類別範例**

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

提示可以嵌入資源並回傳訊息序列來引導 LLM。請參閱官方文件以獲得進階範例和最佳實務。

### 使用通知

通知是來自 MCP 客戶端的 fire-and-forget 訊息，它們總是回傳 HTTP 202 Accepted 而沒有回應主體。它們非常適合記錄、進度追蹤、事件處理和觸發背景程序，而不會阻塞客戶端。

#### 建立通知處理器

**基本指令用法：**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**進階指令功能：**

```bash
# 互動模式 - 如果未指定方法則提示輸入
php artisan make:mcp-notification MyHandler

# 自動方法前綴處理
php artisan make:mcp-notification StatusHandler --method=status  # 變成 notifications/status

# 類別名稱標準化 
php artisan make:mcp-notification "user activity"  # 變成 UserActivityHandler
```

該指令提供：
- 當未指定 `--method` 時**互動式方法提示**
- 帶有複製貼上就緒程式碼的**自動註冊指南**
- 帶有 curl 指令的**內建測試範例** 
- **全面的使用說明**和常見用例

#### 通知處理器架構

每個通知處理器必須實作抽象類別 `NotificationHandler`：

```php
abstract class NotificationHandler
{
    // 必需：訊息類型（通常是 ProcessMessageType::HTTP）
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // 必需：要處理的通知方法  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // 必需：執行通知邏輯
    abstract public function execute(?array $params = null): void;
}
```

**關鍵架構組件：**

- **`MESSAGE_TYPE`**：標準通知通常使用 `ProcessMessageType::HTTP`
- **`HANDLE_METHOD`**：此處理器處理的 JSON-RPC 方法（必須以 `notifications/` 開頭）
- **`execute()`**：包含您的通知邏輯 - 回傳 void（不發送回應）
- **建構函式驗證**：自動驗證必需常數是否已定義

#### 內建通知處理器

套件包含四個為常見 MCP 場景預建的處理器：

**1. InitializedHandler (`notifications/initialized`)**
- **目的**：在成功握手後處理客戶端初始化確認
- **參數**：客戶端資訊和能力
- **用法**：會話追蹤、客戶端記錄、初始化事件

**2. ProgressHandler (`notifications/progress`)**
- **目的**：處理長時間執行操作的進度更新
- **參數**： 
  - `progressToken` (string)：操作的唯一識別符
  - `progress` (number)：目前進度值
  - `total` (number，可選)：用於百分比計算的總進度值
- **用法**：即時進度追蹤、上傳監控、任務完成

**3. CancelledHandler (`notifications/cancelled`)**
- **目的**：處理請求取消通知
- **參數**：
  - `requestId` (string)：要取消的請求 ID
  - `reason` (string，可選)：取消原因
- **用法**：背景作業終止、資源清理、操作中止

**4. MessageHandler (`notifications/message`)**
- **目的**：處理一般記錄和通訊訊息
- **參數**：
  - `level` (string)：記錄層級（info、warning、error、debug）
  - `message` (string)：訊息內容
  - `logger` (string，可選)：記錄器名稱
- **用法**：客戶端記錄、除錯、一般通訊

#### 常見場景的處理器範例

```php
// 檔案上傳進度追蹤
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
            
            // 廣播即時更新
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// 使用者活動和稽核記錄
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
        
        // 為敏感操作觸發安全警報
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// 背景任務觸發
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

#### 註冊通知處理器

**在您的服務提供者中：**

```php
// 在 AppServiceProvider 或專用的 MCP 服務提供者中
public function boot()
{
    $server = app(MCPServer::class);
    
    // 註冊內建處理器（可選 - 預設註冊）
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // 註冊自訂處理器
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### 測試通知

**使用 curl 測試通知處理器：**

```bash
# 測試進度通知
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
# 預期：HTTP 202 且主體為空

# 測試使用者活動通知  
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
# 預期：HTTP 202 且主體為空

# 測試取消通知
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
# 預期：HTTP 202 且主體為空
```

**重要測試注意事項：**
- 通知回傳 **HTTP 202**（從不回傳 200）
- 回應主體**總是空的**
- 不發送 JSON-RPC 回應訊息
- 檢查伺服器記錄以驗證通知處理

#### 錯誤處理和驗證

**常見驗證模式：**

```php
public function execute(?array $params = null): void
{
    // 驗證必需參數
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // 不要拋出例外 - 通知應該容錯
    }
    
    // 驗證參數型別
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // 使用預設值安全提取參數
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // 處理通知...
}
```

**錯誤處理最佳實務：**
- **記錄錯誤**而不是拋出例外
- **使用防禦性程式設計**，進行空值檢查和預設值
- **優雅失敗** - 不要破壞客戶端的工作流程
- **驗證輸入**但在可能時繼續處理
- 透過記錄和指標**監控通知**

### 測試 MCP 工具

套件包含一個特殊指令，用於測試你的 MCP 工具而無需真正的 MCP 客戶端：

```bash
# 互動式測試特定工具
php artisan mcp:test-tool MyCustomTool

# 列出所有可用工具
php artisan mcp:test-tool --list

# 使用特定 JSON 輸入測試
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

這透過以下方式幫助你快速開發和除錯工具：

- 顯示工具的輸入 schema 並驗證輸入
- 使用你提供的輸入執行工具
- 顯示格式化的結果或詳細的錯誤資訊
- 支援複雜的輸入類型，包括物件和陣列

### 使用 Inspector 視覺化 MCP 工具

你也可以使用 Model Context Protocol Inspector 來視覺化和測試你的 MCP 工具：

```bash
# 不安裝直接執行 MCP Inspector
npx @modelcontextprotocol/inspector node build/index.js
```

這通常會在 `localhost:6274` 開啟一個網頁介面。要測試你的 MCP 伺服器：

1. **警告**：`php artisan serve` **無法**與此套件一起使用，因為它無法同時處理多個 PHP 連線。由於 MCP SSE 需要同時處理多個連線，你必須使用以下替代方案之一：

   - **Laravel Octane**（最簡單的選項）：

     ```bash
     # 安裝並設定 Laravel Octane 與 FrankenPHP（推薦）
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # 啟動 Octane 伺服器
     php artisan octane:start
     ```

     > **重要**：安裝 Laravel Octane 時，請確保使用 FrankenPHP 作為伺服器。由於與 SSE 連線的相容性問題，套件可能無法與 RoadRunner 正常運作。如果你能幫助修復這個 RoadRunner 相容性問題，請提交 Pull Request - 我們非常感謝你的貢獻！

     詳情請參閱 [Laravel Octane 文件](https://laravel.com/docs/12.x/octane)

   - **正式環境級選項**：
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 自訂 Docker 設定

   * 任何適當支援 SSE 串流的網頁伺服器（僅舊版 SSE 提供者需要）

2. 在 Inspector 介面中，輸入你的 Laravel 伺服器的 MCP 端點 URL（例如 `http://localhost:8000/mcp`）。如果你使用舊版 SSE 提供者，請改用 SSE URL（`http://localhost:8000/mcp/sse`）。
3. 連線並視覺化探索可用工具

MCP 端點遵循模式：`http://[your-laravel-server]/[default_path]`，其中 `default_path` 在你的 `config/mcp-server.php` 檔案中定義。

## 進階功能

### 使用 SSE 適配器的 Pub/Sub 架構（舊版提供者）

套件透過其適配器系統實作發布/訂閱 (pub/sub) 訊息模式：

1. **發布者（伺服器）**：當客戶端傳送請求到 `/message` 端點時，伺服器處理這些請求並透過配置的適配器發布回應。

2. **訊息代理（適配器）**：適配器（例如 Redis）為每個客戶端維護訊息佇列，由唯一的客戶端 ID 識別。這提供可靠的非同步通訊層。

3. **訂閱者（SSE 連線）**：長時間的 SSE 連線訂閱其各自客戶端的訊息並即時傳遞。這僅適用於使用舊版 SSE 提供者時。

此架構實現：

- 可擴展的即時通訊
- 即使在暫時斷線期間也能可靠地傳遞訊息
- 高效處理多個並發客戶端連線
- 分散式伺服器部署的潛力

### Redis 適配器配置

預設的 Redis 適配器可以如下配置：

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Redis 鍵的前綴
        'connection' => 'default', // 來自 database.php 的 Redis 連線
        'ttl' => 100,              // 訊息 TTL（秒）
    ],
],
```

## 翻譯 README.md

使用 Claude API 將此 README 翻譯成其他語言（平行處理）：

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

你也可以翻譯特定語言：

```bash
python scripts/translate_readme.py es ko
```

## v2.0.0 遷移說明

v2.0.0 已正式發布。若你從 v1.x 升級，請套用以下變更。

### v2.0.0 的主要變更

- `messageType(): ProcessMessageType` 已移除。
- `isStreaming(): bool` 執行階段不再使用（可選清理）。
- `ProcessMessageType::SSE` 已移除。
- 僅支援 Streamable HTTP（`/sse` 與 `/message` 已移除）。
- config 方式的 MCP 設定鍵（`server_provider`、`sse_adapter`、`adapters`、`enabled`）已移除。

### 遷移方式

- 透過 `Route::mcp(...)`（Laravel）或 `McpRoute::register(...)`（Lumen）在路由中直接註冊 MCP 端點。
- 將 server info/tools/resources/templates/prompts 從 config 遷移到 route builder 鏈式設定。
- 執行 `php artisan mcp:migrate-tools` 清理舊版工具簽名。
- 將 MCP 客戶端端點更新為實際路由路徑（例如 `/mcp`）。
- 完整步驟請參考：[v2.0.0 遷移指南](docs/migrations/v2.0.0-migration.md)。

### 遷移後驗證

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
vendor/bin/pest
vendor/bin/phpstan analyse
```

## 授權

此專案採用 MIT 授權條款發布。