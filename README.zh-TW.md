<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  一個強大的 Laravel 套件，用於無縫建構模型上下文協議伺服器
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="建構狀態"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="總下載量"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="最新穩定版"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="授權條款"></a>
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
  <a href="README.pl.md">Polski</a>
</p>

## 概述

Laravel MCP Server 是個超強的套件，讓你在 Laravel 簡單建立 MCP 伺服器。**跟其他用 stdio 的 Laravel MCP 套件不一樣**，這個套件**用的是 SSE**，提供更安全又好管的方式。

### 為什麼選擇 SSE 而非 STDIO？

雖然 stdio 簡單直接且在 MCP 實作中廣泛使用，但在企業環境中存在顯著的安全隱憂：

- **安全風險**：STDIO 傳輸可能會暴露內部系統細節和 API 規格
- **資料保護**：組織需要保護專有 API 端點和內部系統架構
- **控制能力**：SSE 提供對 LLM 客戶端與應用程式之間通訊通道的更佳控制

用 SSE 建立 MCP 伺服器，公司可以：

- 只開放需要的工具和資源，保護自家 API 的細節
- 更好地控制驗證和授權流程

主要優點：

- 快速簡單，直接在現有 Laravel 專案整合 SSE
- 完全支援最新版 Laravel 和 PHP
- 跑得快，即時資料處理效能好
- 企業級安全性，適合商業用途

## 主要特色

- 透過伺服器傳送事件（SSE）整合支援即時通訊
- 實作符合模型上下文協議規範的工具和資源
- 基於轉接器的設計架構，採用發佈/訂閱訊息模式（從 Redis 開始，計劃新增更多轉接器）
- 簡單的路由和中介軟體配置

## 系統要求

- PHP >=8.2
- Laravel >=10.x

## 安裝

1. 透過 Composer 安裝套件：

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 發佈配置檔案：
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 基本用法

### 建立和新增自訂工具

該套件提供了便捷的 Artisan 指令來產生新工具：

```bash
php artisan make:mcp-tool MyCustomTool
```

此指令：

- 處理各種輸入格式（空格、連字符、混合大小寫）
- 自動將名稱轉換為適當的大小寫格式
- 在 `app/MCP/Tools` 中建立結構良好的工具類別
- 提供自動在配置中註冊工具的選項

你也可以在 `config/mcp-server.php` 中手動建立和註冊工具：

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 工具實作
}
```

### 測試 MCP 工具

該套件包含一個特殊指令，用於在不需要真實 MCP 客戶端的情況下測試你的 MCP 工具：

```bash
# 互動式測試特定工具
php artisan mcp:test-tool MyCustomTool

# 列出所有可用工具
php artisan mcp:test-tool --list

# 使用特定 JSON 輸入進行測試
php artisan mcp:test-tool MyCustomTool --input='{"param":"值"}'
```

這有助於你快速開發和除錯工具：

- 顯示工具的輸入模式並驗證輸入
- 使用你提供的輸入執行工具
- 顯示格式化結果或詳細錯誤資訊
- 支援包括物件和陣列在內的複雜輸入類型

### 用 MCP Inspector 視覺化查看工具

你還可以用 MCP Inspector 來直覺地查看和測試你的 MCP 工具：

```bash
# 不用安裝，直接用 npx 執行
npx @modelcontextprotocol/inspector node build/index.js
```

這會在 `localhost:6274` 開一個網頁界面。測試 MCP 伺服器的方法：

1. **警告**: 無法使用 `php artisan serve` 執行此套件，因為它無法同時處理多個 PHP 連接。由於 MCP SSE 需要同時處理多個連接，你必須使用以下替代方案之一：

   * **Laravel Octane** (最簡單選項):
     ```bash
     # 安裝和設定 Laravel Octane
     composer require laravel/octane
     php artisan octane:install
     
     # 啟動 Octane 伺服器
     php artisan octane:start
     ```
     詳細資訊請參考 [Laravel Octane 文件](https://laravel.com/docs/12.x/octane)
     
   * **生產級選項**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 自定義 Docker 配置
     - 任何正確支援 SSE 串流的 Web 伺服器

2. 在 Inspector 界面輸入你的 SSE URL（比如 `http://localhost:8000/mcp/sse`）
3. 連上後就能超直覺地查看所有工具了

SSE URL 格式是：`http://[你的伺服器位址]/[default_path]/sse`，其中 `default_path` 在 `config/mcp-server.php` 檔案裡設定。

## 進階功能

### 具備 SSE 轉接器的發佈/訂閱架構

該套件透過其轉接器系統實現發佈/訂閱（pub/sub）訊息模式：

1. **發佈者（伺服器）**：當客戶端向 `/message` 端點發送請求時，伺服器處理這些請求並透過配置的轉接器發佈回應。

2. **訊息代理（轉接器）**：轉接器（例如 Redis）為每個客戶端維護訊息佇列，透過唯一的客戶端 ID 識別。這提供了可靠的非同步通訊層。

3. **訂閱者（SSE 連線）**：長期存在的 SSE 連線訂閱各自客戶端的訊息並即時傳遞它們。

這種架構實現了：

- 可擴展的即時通訊
- 即使在臨時斷開連線期間也能可靠地傳遞訊息
- 高效處理多個並發客戶端連線
- 分散式伺服器部署的潛力

### Redis 轉接器配置

預設的 Redis 轉接器可以按如下方式配置：

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Redis 鍵前綴
        'connection' => 'default', // 來自 database.php 的 Redis 連線
        'ttl' => 100,              // 訊息 TTL（秒）
    ],
],
```

## 環境變數

該套件支援以下環境變數，允許在不修改配置檔案的情況下進行配置：

| 變數 | 描述 | 預設值 |
|----------|-------------|--------|
| `MCP_SERVER_ENABLED` | 啟用或停用 MCP 伺服器 | `true` |
| `MCP_REDIS_CONNECTION` | 來自 database.php 的 Redis 連線名稱 | `default` |

### .env 配置範例

```
# 在特定環境中停用 MCP 伺服器
MCP_SERVER_ENABLED=false

# 為 MCP 使用特定的 Redis 連線
MCP_REDIS_CONNECTION=mcp
```

## 授權條款

本專案以 MIT 授權條款發布。
