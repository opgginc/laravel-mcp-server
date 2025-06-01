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

## ⚠️ v1.1.0 版本重大變更

v1.1.0 版本對 `ToolInterface` 引入了重大且破壞性的變更。如果你正在從 v1.0.x 升級，你**必須**更新你的工具實作以符合新的介面。

**`ToolInterface` 的重要變更：**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` 已更新如下：

1.  **新增方法：**

    - `messageType(): ProcessMessageType`
      - 這個方法對於新的 HTTP stream 支援至關重要，用來決定正在處理的訊息類型。

2.  **方法重新命名：**
    - `getName()` 現在是 `name()`
    - `getDescription()` 現在是 `description()`
    - `getInputSchema()` 現在是 `inputSchema()`
    - `getAnnotations()` 現在是 `annotations()`

**如何更新你的工具：**

### v1.1.0 自動化工具遷移

為了協助轉換到 v1.1.0 引入的新 `ToolInterface`，我們提供了一個 Artisan 指令來幫助自動重構你現有的工具：

```bash
php artisan mcp:migrate-tools {path?}
```

**功能說明：**

這個指令會掃描指定目錄中的 PHP 檔案（預設為 `app/MCP/Tools/`）並嘗試：

1.  **識別舊工具：** 尋找實作舊方法簽名的 `ToolInterface` 類別。
2.  **建立備份：** 在進行任何變更之前，會建立原始工具檔案的備份，副檔名為 `.backup`（例如 `YourTool.php.backup`）。如果備份檔案已存在，將跳過原始檔案以防止意外資料遺失。
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

如果你的工具位於 `app/MCP/Tools/` 以外的目錄，可以指定路徑：

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

指令會輸出進度，顯示正在處理、備份和遷移的檔案。請務必檢查工具所做的變更。雖然它力求準確，但複雜或格式異常的工具檔案可能需要手動調整。

這個工具應該能大幅簡化遷移過程，幫助你快速適應新的介面結構。

### 手動遷移

如果你偏好手動遷移工具，以下是比較說明來幫助你調整現有工具：

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

如果你的 v1.0.x 工具長這樣：

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
    // 新增 messageType() 方法
    public function messageType(): ProcessMessageType
    {
        // 回傳適當的訊息類型，例如標準工具
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // 重新命名
    public function description(): string { return 'This is my new tool.'; } // 重新命名
    public function inputSchema(): array { return []; } // 重新命名
    public function annotations(): array { return []; } // 重新命名
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Laravel MCP Server 概述

Laravel MCP Server 是一個強大的套件，專為簡化在 Laravel 應用程式中實作 Model Context Protocol (MCP) 伺服器而設計。**與大多數使用標準輸入/輸出 (stdio) 傳輸的 Laravel MCP 套件不同**，這個套件專注於 **Streamable HTTP** 傳輸，同時仍包含**舊版 SSE provider** 以確保向後相容性，提供安全且可控的整合方法。

### 為什麼選擇 Streamable HTTP 而非 STDIO？

雖然 stdio 簡單直接且在 MCP 實作中廣泛使用，但對企業環境來說有重大的安全隱憂：

- **安全風險**：STDIO 傳輸可能暴露內部系統細節和 API 規格
- **資料保護**：組織需要保護專有 API 端點和內部系統架構
- **控制性**：Streamable HTTP 對 LLM 客戶端與你的應用程式之間的通訊通道提供更好的控制

透過使用 Streamable HTTP 傳輸實作 MCP 伺服器，企業可以：

- 只暴露必要的工具和資源，同時保持專有 API 細節的私密性
- 維持對認證和授權流程的控制

主要優勢：

- 在現有 Laravel 專案中無縫且快速實作 Streamable HTTP
- 支援最新的 Laravel 和 PHP 版本
- 高效的伺服器通訊和即時資料處理
- 為企業環境提供增強的安全性

## 主要功能

- 透過 Streamable HTTP 與 SSE 整合支援即時通訊
- 實作符合 Model Context Protocol 規格的工具和資源
- 基於適配器的設計架構，採用 Pub/Sub 訊息模式（從 Redis 開始，計劃支援更多適配器）
- 簡單的路由和中介軟體配置

### 傳輸 Provider

配置選項 `server_provider` 控制使用哪種傳輸方式。可用的 provider 包括：

1. **streamable_http** – 建議的預設選項。使用標準 HTTP 請求，避免在約一分鐘後關閉 SSE 連線的平台問題（例如許多 serverless 環境）。
2. **sse** – 為向後相容性保留的舊版 provider。它依賴長時間的 SSE 連線，在 HTTP 超時時間短的平台上可能無法正常運作。

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

這個指令：

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
    // 決定工具訊息的處理方式，通常與傳輸方式相關。
    public function messageType(): ProcessMessageType;

    // 你的工具的唯一可呼叫名稱（例如 'get-user-details'）。
    public function name(): string;

    // 工具功能的人類可讀描述。
    public function description(): string;

    // 使用類似 JSON Schema 的結構定義工具的預期輸入參數。
    public function inputSchema(): array;

    // 提供為工具新增任意中繼資料或註解的方式。
    public function annotations(): array;

    // 工具的核心邏輯。接收驗證過的參數並回傳結果。
    public function execute(array $arguments): mixed;
}
```

讓我們深入了解其中一些方法：

**`messageType(): ProcessMessageType`**

這個方法指定工具的訊息處理類型。它回傳一個 `ProcessMessageType` enum 值。可用的類型包括：

- `ProcessMessageType::HTTP`：用於透過標準 HTTP 請求/回應互動的工具。新工具最常用。
- `ProcessMessageType::SSE`：專為與 Server-Sent Events 搭配使用而設計的工具。

對於大多數工具，特別是為主要 `streamable_http` provider 設計的工具，你會回傳 `ProcessMessageType::HTTP`。

**`name(): string`**

這是你工具的識別符。它應該是唯一的。客戶端會使用這個名稱來請求你的工具。例如：`get-weather`、`calculate-sum`。

**`description(): string`**

工具功能的清楚、簡潔描述。這用於文件，MCP 客戶端 UI（如 MCP Inspector）可能會向使用者顯示它。

**`inputSchema(): array`**

這個方法對於定義工具的預期輸入參數至關重要。它應該回傳一個遵循類似 JSON Schema 結構的陣列。這個 schema 用於：

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
        'required' => ['userId'], // 指定哪些屬性是必填的
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

這個方法提供關於工具行為和特性的中繼資料，遵循官方 [MCP Tool Annotations 規格](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations)。註解幫助 MCP 客戶端分類工具、對工具核准做出明智決策，並提供適當的使用者介面。

**標準 MCP 註解：**

Model Context Protocol 定義了幾個客戶端能理解的標準註解：

- **`title`** (string)：工具的人類可讀標題，顯示在客戶端 UI 中
- **`readOnlyHint`** (boolean)：指示工具是否只讀取資料而不修改環境（預設：false）
- **`destructiveHint`** (boolean)：建議工具是否可能執行破壞性操作，如刪除資料（預設：true）
- **`idempotentHint`** (boolean)：指示使用相同參數重複呼叫是否沒有額外效果（預設：false）
- **`openWorldHint`** (boolean)：表示工具是否與本地環境以外的外部實體互動（預設：true）

**重要：** 這些是提示，不是保證。它們幫助客戶端提供更好的使用者體驗，但不應用於安全關鍵決策。

**標準 MCP 註解範例：**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // 工具只讀取使用者資料
        'destructiveHint' => false,    // 工具不刪除或修改資料
        'idempotentHint' => true,      // 可安全多次呼叫
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

### 測試 MCP 工具

套件包含一個特殊指令，用於測試你的 MCP 工具而不需要真正的 MCP 客戶端：

```bash
# 互動式測試特定工具
php artisan mcp:test-tool MyCustomTool

# 列出所有可用工具
php artisan mcp:test-tool --list

# 使用特定 JSON 輸入測試
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

這幫助你快速開發和除錯工具：

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
     # 安裝並設定 Laravel Octane 與 FrankenPHP（建議）
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # 啟動 Octane 伺服器
     php artisan octane:start
     ```

     > **重要**：安裝 Laravel Octane 時，請確保使用 FrankenPHP 作為伺服器。由於 SSE 連線的相容性問題，套件可能無法與 RoadRunner 正常運作。如果你能幫助修復這個 RoadRunner 相容性問題，請提交 Pull Request - 我們非常感謝你的貢獻！

     詳情請參閱 [Laravel Octane 文件](https://laravel.com/docs/12.x/octane)

   - **正式環境等級選項**：
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 自訂 Docker 設定

   * 任何適當支援 SSE streaming 的網頁伺服器（僅舊版 SSE provider 需要）

2. 在 Inspector 介面中，輸入你的 Laravel 伺服器的 MCP 端點 URL（例如 `http://localhost:8000/mcp`）。如果你使用舊版 SSE provider，請改用 SSE URL（`http://localhost:8000/mcp/sse`）。
3. 連線並視覺化探索可用工具

MCP 端點遵循模式：`http://[your-laravel-server]/[default_path]`，其中 `default_path` 在你的 `config/mcp-server.php` 檔案中定義。

## 進階功能

### 使用 SSE 適配器的 Pub/Sub 架構（舊版 provider）

套件透過其適配器系統實作發布/訂閱（pub/sub）訊息模式：

1. **發布者（伺服器）**：當客戶端傳送請求到 `/message` 端點時，伺服器處理這些請求並透過配置的適配器發布回應。

2. **訊息代理（適配器）**：適配器（例如 Redis）為每個客戶端維護訊息佇列，透過唯一的客戶端 ID 識別。這提供可靠的非同步通訊層。

3. **訂閱者（SSE 連線）**：長時間的 SSE 連線訂閱各自客戶端的訊息並即時傳遞。這僅適用於使用舊版 SSE provider 時。

這個架構實現：

- 可擴展的即時通訊
- 即使在暫時斷線期間也能可靠傳遞訊息
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

## 環境變數

套件支援以下環境變數，允許在不修改配置檔案的情況下進行配置：

| 變數                   | 描述                                    | 預設值    |
| ---------------------- | --------------------------------------- | --------- |
| `MCP_SERVER_ENABLED`   | 啟用或停用 MCP 伺服器                   | `true`    |

### .env 配置範例

```
# 在特定環境中停用 MCP 伺服器
MCP_SERVER_ENABLED=false
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

## 授權條款

本專案採用 MIT 授權條款發布。
