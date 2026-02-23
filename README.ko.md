<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Model Context Protocol Server를 원활하게 구축할 수 있는 강력한 Laravel 패키지
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">공식 웹사이트</a>
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

## ⚠️ 버전 정보 및 주요 변경사항

### v1.4.0 변경사항 (최신) 🚀

버전 1.4.0에서는 Swagger/OpenAPI 사양으로부터 강력한 자동 도구 및 리소스 생성 기능을 도입합니다:

**새로운 기능:**
- **Swagger/OpenAPI 도구 및 리소스 생성기**: 모든 Swagger/OpenAPI 사양으로부터 자동으로 MCP 도구 또는 리소스 생성
  - OpenAPI 3.x 및 Swagger 2.0 형식 모두 지원
  - **생성 타입 선택**: 도구(액션용) 또는 리소스(읽기 전용 데이터용)로 생성
  - 그룹 옵션을 통한 대화형 엔드포인트 선택
  - 자동 인증 로직 생성 (API Key, Bearer Token, OAuth2)
  - 읽기 쉬운 클래스 이름을 위한 스마트 네이밍 (해시 기반 operationId 처리)
  - 생성 전 내장 API 테스트
  - 재시도 로직을 포함한 완전한 Laravel HTTP 클라이언트 통합

**사용 예시:**
```bash
# OP.GG API에서 도구 생성
php artisan make:swagger-mcp-tool https://api.op.gg/lol/swagger.json

# 옵션과 함께 사용
php artisan make:swagger-mcp-tool ./api-spec.json --test-api --group-by=tag --prefix=MyApi
```

이 기능은 외부 API를 MCP 서버에 통합하는 데 필요한 시간을 대폭 줄여줍니다!

### v1.3.0 변경사항

버전 1.3.0에서는 더 나은 통신 제어를 위해 `ToolInterface`가 개선되었습니다:

**새로운 기능:**
- 더 명확한 통신 패턴 선택을 위한 `isStreaming(): bool` 메서드 추가
- v1.1.x, v1.2.x에서 v1.3.0으로의 업그레이드를 지원하는 마이그레이션 도구 개선
- 포괄적인 v1.3.0 문서가 포함된 stub 파일 향상

**폐기 예정 기능:**
- `messageType(): ProcessMessageType` 메서드가 폐기 예정됨 (v2.0.0에서 제거 예정)
- 더 나은 명확성과 단순성을 위해 `isStreaming(): bool`을 대신 사용하세요

### v1.1.0의 주요 변경사항

버전 1.1.0에서는 `ToolInterface`에 중요하고 호환성을 깨뜨리는 변경사항이 도입되었습니다. v1.0.x에서 업그레이드하는 경우, 새로운 인터페이스에 맞게 도구 구현을 **반드시** 업데이트해야 합니다.

**`ToolInterface`의 주요 변경사항:**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`가 다음과 같이 업데이트되었습니다:

1.  **새로운 메서드 추가:**

    - `messageType(): ProcessMessageType`
      - 이 메서드는 새로운 HTTP 스트림 지원에 중요하며 처리되는 메시지 유형을 결정합니다.

2.  **메서드 이름 변경:**
    - `getName()`이 `name()`으로 변경
    - `getDescription()`이 `description()`으로 변경
    - `getInputSchema()`가 `inputSchema()`로 변경
    - `getAnnotations()`가 `annotations()`로 변경

**도구 업데이트 방법:**

### v1.1.0을 위한 자동화된 도구 마이그레이션

v1.1.0에서 도입된 새로운 `ToolInterface`로의 전환을 돕기 위해, 기존 도구의 리팩터링을 자동화할 수 있는 Artisan 명령어를 포함했습니다:

```bash
php artisan mcp:migrate-tools {path?}
```

**기능:**

이 명령어는 지정된 디렉터리(기본값: `app/MCP/Tools/`)의 PHP 파일을 스캔하여 다음을 시도합니다:

1.  **기존 도구 식별:** 이전 메서드 시그니처로 `ToolInterface`를 구현하는 클래스를 찾습니다.
2.  **백업 생성:** 변경하기 전에 원본 도구 파일의 백업을 `.backup` 확장자로 생성합니다(예: `YourTool.php.backup`). 백업 파일이 이미 존재하면 실수로 데이터를 잃는 것을 방지하기 위해 원본 파일을 건너뜁니다.
3.  **도구 리팩터링:**
    - 메서드 이름 변경:
      - `getName()`을 `name()`으로
      - `getDescription()`을 `description()`으로
      - `getInputSchema()`를 `inputSchema()`로
      - `getAnnotations()`를 `annotations()`로
    - 새로운 `messageType()` 메서드 추가, 기본값으로 `ProcessMessageType::SSE`를 반환
    - `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` 구문이 있는지 확인

**사용법:**

`opgginc/laravel-mcp-server` 패키지를 v1.1.0 이상으로 업데이트한 후, v1.0.x용으로 작성된 기존 도구가 있다면 이 명령어를 실행하는 것을 강력히 권장합니다:

```bash
php artisan mcp:migrate-tools
```

도구가 `app/MCP/Tools/` 이외의 디렉터리에 있다면 경로를 지정할 수 있습니다:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

명령어는 처리 중인 파일, 백업 중인 파일, 마이그레이션 중인 파일을 표시하며 진행 상황을 출력합니다. 도구가 만든 변경사항을 항상 검토하세요. 정확성을 목표로 하지만, 복잡하거나 비정상적으로 포맷된 도구 파일은 수동 조정이 필요할 수 있습니다.

이 도구는 마이그레이션 과정을 크게 간소화하고 새로운 인터페이스 구조에 빠르게 적응할 수 있도록 도와줍니다.

### 수동 마이그레이션

도구를 수동으로 마이그레이션하려는 경우, 기존 도구를 적응시키는 데 도움이 되는 비교 자료입니다:

**v1.0.x `ToolInterface`:**

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

**v1.1.0 `ToolInterface` (새 버전):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // 새로운 메서드
    public function name(): string;                     // 이름 변경
    public function description(): string;              // 이름 변경
    public function inputSchema(): array;               // 이름 변경
    public function annotations(): array;               // 이름 변경
    public function execute(array $arguments): mixed;   // 변경 없음
}
```

**업데이트된 도구 예시:**

v1.0.x 도구가 다음과 같았다면:

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

v1.1.0을 위해 다음과 같이 업데이트해야 합니다:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // enum import

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
        return false; // 대부분의 도구는 false를 반환해야 함
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Laravel MCP Server 개요

Laravel MCP Server는 Laravel 애플리케이션에서 Model Context Protocol (MCP) 서버 구현을 간소화하도록 설계된 강력한 패키지입니다. **Standard Input/Output (stdio) 전송을 사용하는 대부분의 Laravel MCP 패키지와 달리**, 이 패키지는 **Streamable HTTP** 전송에 중점을 두고 있으며 하위 호환성을 위한 **레거시 SSE 제공자**도 포함하여 안전하고 제어된 통합 방법을 제공합니다.

### STDIO 대신 Streamable HTTP를 사용하는 이유는?

stdio는 간단하고 MCP 구현에서 널리 사용되지만, 기업 환경에서는 심각한 보안 문제가 있습니다:

- **보안 위험**: STDIO 전송은 내부 시스템 세부사항과 API 사양을 잠재적으로 노출할 수 있습니다
- **데이터 보호**: 조직은 독점 API 엔드포인트와 내부 시스템 아키텍처를 보호해야 합니다
- **제어**: Streamable HTTP는 LLM 클라이언트와 애플리케이션 간의 통신 채널에 대한 더 나은 제어를 제공합니다

Streamable HTTP 전송으로 MCP 서버를 구현함으로써 기업은 다음을 할 수 있습니다:

- 독점 API 세부사항을 비공개로 유지하면서 필요한 도구와 리소스만 노출
- 인증 및 권한 부여 프로세스에 대한 제어 유지

주요 이점:

- 기존 Laravel 프로젝트에서 Streamable HTTP의 원활하고 빠른 구현
- 최신 Laravel 및 PHP 버전 지원
- 효율적인 서버 통신 및 실시간 데이터 처리
- 기업 환경을 위한 향상된 보안

## 주요 기능

- SSE 통합을 통한 Streamable HTTP를 통한 실시간 통신 지원
- Model Context Protocol 사양을 준수하는 도구 및 리소스 구현
- Pub/Sub 메시징 패턴을 사용한 어댑터 기반 설계 아키텍처 (Redis부터 시작, 더 많은 어댑터 계획 중)
- 간단한 라우팅 및 미들웨어 구성

### 전송 제공자

구성 옵션 `server_provider`는 사용할 전송을 제어합니다. 사용 가능한 제공자는 다음과 같습니다:

1. **streamable_http** – 권장되는 기본값입니다. 표준 HTTP 요청을 사용하며 약 1분 후 SSE 연결을 닫는 플랫폼(예: 많은 서버리스 환경)에서의 문제를 방지합니다.
2. **sse** – 하위 호환성을 위해 유지되는 레거시 제공자입니다. 장기간 지속되는 SSE 연결에 의존하며 짧은 HTTP 타임아웃을 가진 플랫폼에서는 작동하지 않을 수 있습니다.

MCP 프로토콜은 "Streamable HTTP SSE" 모드도 정의하지만, 이 패키지는 이를 구현하지 않으며 구현할 계획도 없습니다.

## 요구사항

- PHP >=8.2
- Laravel >=10.x

## 설치

1. Composer를 통해 패키지를 설치합니다:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 구성 파일을 게시합니다:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 기본 사용법


### 사용자 정의 도구 생성 및 추가

패키지는 새로운 도구를 생성하는 편리한 Artisan 명령어를 제공합니다:

```bash
php artisan make:mcp-tool MyCustomTool
```

이 명령어는:

- 다양한 입력 형식(공백, 하이픈, 대소문자 혼합) 처리
- 이름을 적절한 케이스 형식으로 자동 변환
- `app/MCP/Tools`에 적절히 구조화된 도구 클래스 생성
- 구성에서 도구를 자동으로 등록할 것인지 제안

`config/mcp-server.php`에서 도구를 수동으로 생성하고 등록할 수도 있습니다:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 도구 구현
}
```

### 도구 구조 이해하기 (ToolInterface)

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`를 구현하여 도구를 생성할 때 여러 메서드를 정의해야 합니다. 각 메서드와 그 목적에 대한 분석은 다음과 같습니다:

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

    // v1.3.0의 새로운 기능: 이 도구가 표준 HTTP 대신 스트리밍(SSE)을 필요로 하는지 결정합니다.
    public function isStreaming(): bool;

    // 도구의 고유하고 호출 가능한 이름 (예: 'get-user-details').
    public function name(): string;

    // 도구가 수행하는 작업에 대한 사람이 읽을 수 있는 설명.
    public function description(): string;

    // JSON Schema와 유사한 구조를 사용하여 도구의 예상 입력 매개변수를 정의합니다.
    public function inputSchema(): array;

    // 도구에 임의의 메타데이터나 주석을 추가하는 방법을 제공합니다.
    public function annotations(): array;

    // 도구의 핵심 로직. 검증된 인수를 받고 결과를 반환합니다.
    public function execute(array $arguments): mixed;
}
```

이러한 메서드 중 일부를 더 자세히 살펴보겠습니다:

**`messageType(): ProcessMessageType` (v1.3.0에서 폐기 예정)**

⚠️ **이 메서드는 v1.3.0부터 폐기 예정입니다.** 더 나은 명확성을 위해 `isStreaming(): bool`을 대신 사용하세요.

이 메서드는 도구의 메시지 처리 유형을 지정합니다. `ProcessMessageType` enum 값을 반환합니다. 사용 가능한 유형은 다음과 같습니다:

- `ProcessMessageType::HTTP`: 표준 HTTP 요청/응답을 통해 상호작용하는 도구용. 새로운 도구에서 가장 일반적입니다.
- `ProcessMessageType::SSE`: Server-Sent Events와 함께 작동하도록 특별히 설계된 도구용.

대부분의 도구, 특히 주요 `streamable_http` 제공자용으로 설계된 도구의 경우 `ProcessMessageType::HTTP`를 반환합니다.

**`isStreaming(): bool` (v1.3.0의 새로운 기능)**

이것은 통신 패턴을 제어하는 새롭고 더 직관적인 메서드입니다:

- `return false`: 표준 HTTP 요청/응답 사용 (대부분의 도구에 권장)
- `return true`: 실시간 스트리밍을 위한 Server-Sent Events 사용

다음과 같은 실시간 스트리밍 기능이 특별히 필요한 경우가 아니라면 대부분의 도구는 `false`를 반환해야 합니다:
- 장시간 실행되는 작업의 실시간 진행 상황 업데이트
- 라이브 데이터 피드 또는 모니터링 도구
- 양방향 통신이 필요한 대화형 도구

**`name(): string`**

이것은 도구의 식별자입니다. 고유해야 합니다. 클라이언트는 이 이름을 사용하여 도구를 요청합니다. 예: `get-weather`, `calculate-sum`.

**`description(): string`**

도구 기능에 대한 명확하고 간결한 설명입니다. 이는 문서에 사용되며, MCP 클라이언트 UI(예: MCP Inspector)에서 사용자에게 표시할 수 있습니다.

**`inputSchema(): array`**

이 메서드는 도구의 예상 입력 매개변수를 정의하는 데 중요합니다. JSON Schema와 유사한 구조를 따르는 배열을 반환해야 합니다. 이 스키마는 다음과 같이 사용됩니다:

- 클라이언트가 전송할 데이터를 이해하기 위해
- 서버나 클라이언트에서 입력 검증을 위해 잠재적으로 사용
- MCP Inspector와 같은 도구에서 테스트용 폼을 생성하기 위해

**`inputSchema()` 예시:**

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
                'default' => false, // 기본값을 지정할 수 있습니다
            ],
        ],
        'required' => ['userId'], // 필수 속성을 지정합니다
    ];
}
```

`execute` 메서드에서 들어오는 인수를 검증할 수 있습니다. `HelloWorldTool` 예시는 이를 위해 `Illuminate\Support\Facades\Validator`를 사용합니다:

```php
// execute() 메서드 내부:
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
// 검증된 $arguments['userId']와 $arguments['includeDetails']로 진행
```

**`annotations(): array`**

이 메서드는 공식 [MCP Tool Annotations 사양](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations)에 따라 도구의 동작과 특성에 대한 메타데이터를 제공합니다. 주석은 MCP 클라이언트가 도구를 분류하고, 도구 승인에 대한 정보에 입각한 결정을 내리며, 적절한 사용자 인터페이스를 제공하는 데 도움이 됩니다.

**표준 MCP 주석:**

Model Context Protocol은 클라이언트가 이해하는 여러 표준 주석을 정의합니다:

- **`title`** (string): 클라이언트 UI에 표시되는 도구의 사람이 읽을 수 있는 제목
- **`readOnlyHint`** (boolean): 도구가 환경을 수정하지 않고 데이터만 읽는지 나타냄 (기본값: false)
- **`destructiveHint`** (boolean): 도구가 데이터 삭제와 같은 파괴적인 작업을 수행할 수 있는지 제안 (기본값: true)
- **`idempotentHint`** (boolean): 동일한 인수로 반복 호출해도 추가 효과가 없는지 나타냄 (기본값: false)
- **`openWorldHint`** (boolean): 도구가 로컬 환경을 넘어 외부 엔터티와 상호작용하는지 신호 (기본값: true)

**중요:** 이것들은 힌트이지 보장이 아닙니다. 클라이언트가 더 나은 사용자 경험을 제공하는 데 도움이 되지만 보안이 중요한 결정에는 사용해서는 안 됩니다.

**표준 MCP 주석을 사용한 예시:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // 도구는 사용자 데이터만 읽음
        'destructiveHint' => false,    // 도구는 데이터를 삭제하거나 수정하지 않음
        'idempotentHint' => true,      // 여러 번 호출해도 안전함
        'openWorldHint' => false,      // 도구는 로컬 데이터베이스에만 접근
    ];
}
```

**도구 유형별 실제 예시:**

```php
// 데이터베이스 쿼리 도구
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

// 게시물 삭제 도구
public function annotations(): array
{
    return [
        'title' => 'Blog Post Deletion Tool',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // 게시물을 삭제할 수 있음
        'idempotentHint' => false,     // 두 번 삭제하면 다른 효과가 있음
        'openWorldHint' => false,
    ];
}

// API 통합 도구
public function annotations(): array
{
    return [
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // 외부 날씨 API에 접근
    ];
}
```

특정 애플리케이션 요구사항을 위한 **사용자 정의 주석**도 추가할 수 있습니다:

```php
public function annotations(): array
{
    return [
        // 표준 MCP 주석
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // 애플리케이션을 위한 사용자 정의 주석
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### 리소스 작업

리소스는 MCP 클라이언트가 읽을 수 있는 서버의 데이터를 노출합니다. 이들은
**애플리케이션 제어**되며, 클라이언트가 언제 어떻게 사용할지 결정합니다.
Artisan 도우미를 사용하여 `app/MCP/Resources`와
`app/MCP/ResourceTemplates`에 구체적인 리소스나 URI 템플릿을 생성하세요:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

생성된 클래스를 `config/mcp-server.php`의 `resources`와
`resource_templates` 배열에 등록하세요. 각 리소스 클래스는 기본
`Resource` 클래스를 확장하고 `text` 또는
`blob` 콘텐츠를 반환하는 `read()` 메서드를 구현합니다. 템플릿은 `ResourceTemplate`을 확장하고 클라이언트가 사용할 수 있는 동적 URI 패턴을 설명합니다. 리소스는 `file:///logs/app.log`와 같은 URI로 식별되며 선택적으로 `mimeType`이나 `size`와 같은 메타데이터를 정의할 수 있습니다.

**동적 목록을 가진 리소스 템플릿**: 템플릿은 선택적으로 `list()` 메서드를 구현하여 템플릿 패턴과 일치하는 구체적인 리소스 인스턴스를 제공할 수 있습니다. 이를 통해 클라이언트가 사용 가능한 리소스를 동적으로 발견할 수 있습니다. `list()` 메서드는 ResourceTemplate 인스턴스가 템플릿의 `read()` 메서드를 통해 읽을 수 있는 특정 리소스 목록을 생성할 수 있게 합니다.

`resources/list` 엔드포인트를 사용하여 사용 가능한 리소스를 나열하고 `resources/read`로 내용을 읽으세요. `resources/list` 엔드포인트는 정적 리소스와 `list()` 메서드를 구현하는 템플릿에서 동적으로 생성된 리소스를 모두 포함한 구체적인 리소스 배열을 반환합니다:

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

**동적 리소스 읽기**: 리소스 템플릿은 클라이언트가 동적 리소스 식별자를 구성할 수 있게 하는 URI 템플릿 패턴(RFC 6570)을 지원합니다. 클라이언트가 템플릿 패턴과 일치하는 리소스 URI를 요청하면, 추출된 매개변수와 함께 템플릿의 `read()` 메서드가 호출되어 리소스 콘텐츠를 생성합니다.

예시 워크플로:
1. 템플릿이 패턴 정의: `"database://users/{userId}/profile"`
2. 클라이언트가 요청: `"database://users/123/profile"`
3. 템플릿이 `{userId: "123"}`을 추출하고 `read()` 메서드 호출
4. 템플릿이 사용자 ID 123의 사용자 프로필 데이터 반환

`resources/templates/list` 엔드포인트를 사용하여 템플릿을 별도로 나열할 수도 있습니다:

```bash
# 리소스 템플릿만 나열
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

Laravel MCP 서버를 원격으로 실행할 때, HTTP 전송은 표준 JSON-RPC 요청과 함께 작동합니다. 리소스를 나열하고 읽는 간단한 `curl` 예시입니다:

```bash
# 리소스 나열
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# 특정 리소스 읽기
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

서버는 HTTP 연결을 통해 스트리밍된 JSON 메시지로 응답하므로, 점진적 출력을 보려면 `curl --no-buffer`를 사용할 수 있습니다.

### 프롬프트 작업

프롬프트는 도구나 사용자가 요청할 수 있는 인수 지원이 있는 재사용 가능한 텍스트 스니펫을 제공합니다.
다음을 사용하여 `app/MCP/Prompts`에 프롬프트 클래스를 생성하세요:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

`config/mcp-server.php`의 `prompts` 아래에 등록하세요. 각 프롬프트 클래스는
`Prompt` 기본 클래스를 확장하고 다음을 정의합니다:
- `name`: 고유 식별자 (예: "welcome-user")
- `description`: 선택적 사람이 읽을 수 있는 설명  
- `arguments`: 이름, 설명, 필수 필드가 있는 인수 정의 배열
- `text`: `{username}`과 같은 플레이스홀더가 있는 프롬프트 템플릿

`prompts/list` 엔드포인트를 통해 프롬프트를 나열하고 인수와 함께 `prompts/get`을 사용하여 가져오세요:

```bash
# 인수와 함께 환영 프롬프트 가져오기
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### MCP 프롬프트

도구나 리소스를 참조하는 프롬프트를 작성할 때 [공식 프롬프트 가이드라인](https://modelcontextprotocol.io/docs/concepts/prompts)을 참조하세요. 프롬프트는 인수를 받아들이고, 리소스 컨텍스트를 포함하며, 심지어 다단계 워크플로를 설명할 수 있는 재사용 가능한 템플릿입니다.

**프롬프트 구조**

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

클라이언트는 `prompts/list`를 통해 프롬프트를 발견하고 `prompts/get`으로 특정 프롬프트를 요청합니다:

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

**프롬프트 클래스 예시**

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

프롬프트는 리소스를 포함하고 LLM을 안내하는 메시지 시퀀스를 반환할 수 있습니다. 고급 예시와 모범 사례는 공식 문서를 참조하세요.

### 알림 작업

알림은 MCP 클라이언트로부터의 fire-and-forget 메시지로, 항상 응답 본문 없이 HTTP 202 Accepted를 반환합니다. 클라이언트를 차단하지 않고 로깅, 진행 상황 추적, 이벤트 처리, 백그라운드 프로세스 트리거에 완벽합니다.

#### 알림 핸들러 생성

**기본 명령어 사용법:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**고급 명령어 기능:**

```bash
# 대화형 모드 - 메서드가 지정되지 않으면 메서드를 묻습니다
php artisan make:mcp-notification MyHandler

# 자동 메서드 접두사 처리
php artisan make:mcp-notification StatusHandler --method=status  # notifications/status가 됩니다

# 클래스 이름 정규화 
php artisan make:mcp-notification "user activity"  # UserActivityHandler가 됩니다
```

이 명령어는 다음을 제공합니다:
- `--method`가 지정되지 않았을 때 **대화형 메서드 프롬프트**
- 복사-붙여넣기가 가능한 코드가 포함된 **자동 등록 가이드**
- curl 명령어가 포함된 **내장 테스트 예시** 
- **포괄적인 사용 지침**과 일반적인 사용 사례

#### 알림 핸들러 아키텍처

각 알림 핸들러는 `NotificationHandler` 추상 클래스를 구현해야 합니다:

```php
abstract class NotificationHandler
{
    // 필수: 메시지 유형 (일반적으로 ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // 필수: 처리할 알림 메서드  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // 필수: 알림 로직 실행
    abstract public function execute(?array $params = null): void;
}
```

**주요 아키텍처 구성 요소:**

- **`MESSAGE_TYPE`**: 표준 알림의 경우 일반적으로 `ProcessMessageType::HTTP`
- **`HANDLE_METHOD`**: 이 핸들러가 처리하는 JSON-RPC 메서드 (`notifications/`로 시작해야 함)
- **`execute()`**: 알림 로직 포함 - void 반환 (응답 전송 안 함)
- **생성자 검증**: 필수 상수가 정의되었는지 자동으로 검증

#### 내장 알림 핸들러

패키지에는 일반적인 MCP 시나리오를 위한 4개의 사전 구축된 핸들러가 포함되어 있습니다:

**1. InitializedHandler (`notifications/initialized`)**
- **목적**: 성공적인 핸드셰이크 후 클라이언트 초기화 확인 처리
- **매개변수**: 클라이언트 정보 및 기능
- **사용법**: 세션 추적, 클라이언트 로깅, 초기화 이벤트

**2. ProgressHandler (`notifications/progress`)**
- **목적**: 장시간 실행되는 작업의 진행 상황 업데이트 처리
- **매개변수**: 
  - `progressToken` (string): 작업의 고유 식별자
  - `progress` (number): 현재 진행 값
  - `total` (number, 선택): 백분율 계산을 위한 총 진행 값
- **사용법**: 실시간 진행 상황 추적, 업로드 모니터링, 작업 완료

**3. CancelledHandler (`notifications/cancelled`)**
- **목적**: 요청 취소 알림 처리
- **매개변수**:
  - `requestId` (string): 취소할 요청의 ID
  - `reason` (string, 선택): 취소 이유
- **사용법**: 백그라운드 작업 종료, 리소스 정리, 작업 중단

**4. MessageHandler (`notifications/message`)**
- **목적**: 일반 로깅 및 통신 메시지 처리
- **매개변수**:
  - `level` (string): 로그 레벨 (info, warning, error, debug)
  - `message` (string): 메시지 내용
  - `logger` (string, 선택): 로거 이름
- **사용법**: 클라이언트 측 로깅, 디버깅, 일반 통신

#### 일반적인 시나리오를 위한 핸들러 예시

```php
// 파일 업로드 진행 상황 추적
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
            
            // 실시간 업데이트 브로드캐스트
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// 사용자 활동 및 감사 로깅
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
        
        // 민감한 작업에 대한 보안 알림 트리거
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// 백그라운드 작업 트리거
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

#### 알림 핸들러 등록

**서비스 제공자에서:**

```php
// AppServiceProvider 또는 전용 MCP 서비스 제공자에서
public function boot()
{
    $server = app(MCPServer::class);
    
    // 내장 핸들러 등록 (선택사항 - 기본적으로 등록됨)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // 사용자 정의 핸들러 등록
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### 알림 테스트

**curl을 사용한 알림 핸들러 테스트:**

```bash
# 진행 상황 알림 테스트
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
# 예상: 빈 본문으로 HTTP 202

# 사용자 활동 알림 테스트  
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
# 예상: 빈 본문으로 HTTP 202

# 취소 알림 테스트
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
# 예상: 빈 본문으로 HTTP 202
```

**주요 테스트 참고사항:**
- 알림은 **HTTP 202**를 반환 (200은 절대 안 함)
- 응답 본문은 **항상 비어 있음**
- JSON-RPC 응답 메시지가 전송되지 않음
- 알림 처리 확인을 위해 서버 로그 확인

#### 오류 처리 및 검증

**일반적인 검증 패턴:**

```php
public function execute(?array $params = null): void
{
    // 필수 매개변수 검증
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // 예외를 던지지 마세요 - 알림은 내결함성이 있어야 합니다
    }
    
    // 매개변수 유형 검증
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // 기본값으로 안전한 매개변수 추출
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // 알림 처리...
}
```

**오류 처리 모범 사례:**
- 예외를 던지는 대신 **오류 로깅**
- null 체크와 기본값으로 **방어적 프로그래밍** 사용
- **우아하게 실패** - 클라이언트 워크플로를 깨뜨리지 마세요
- **입력 검증**하되 가능하면 처리 계속
- 로깅과 메트릭을 통한 **알림 모니터링**

### MCP 도구 테스트

패키지에는 실제 MCP 클라이언트 없이도 MCP 도구를 테스트할 수 있는 특별한 명령어가 포함되어 있습니다:

```bash
# 특정 도구를 대화형으로 테스트
php artisan mcp:test-tool MyCustomTool

# 사용 가능한 모든 도구 나열
php artisan mcp:test-tool --list

# 특정 JSON 입력으로 테스트
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

이를 통해 다음과 같은 방법으로 도구를 빠르게 개발하고 디버그할 수 있습니다:

- 도구의 입력 스키마 표시 및 입력 검증
- 제공된 입력으로 도구 실행
- 포맷된 결과 또는 상세한 오류 정보 표시
- 객체와 배열을 포함한 복잡한 입력 유형 지원

### Inspector로 MCP 도구 시각화

Model Context Protocol Inspector를 사용하여 MCP 도구를 시각화하고 테스트할 수도 있습니다:

```bash
# 설치 없이 MCP Inspector 실행
npx @modelcontextprotocol/inspector node build/index.js
```

이는 일반적으로 `localhost:6274`에서 웹 인터페이스를 엽니다. MCP 서버를 테스트하려면:

1. **경고**: `php artisan serve`는 여러 PHP 연결을 동시에 처리할 수 없기 때문에 이 패키지와 함께 사용할 수 없습니다. MCP SSE는 여러 연결을 동시에 처리해야 하므로 다음 대안 중 하나를 사용해야 합니다:

   - **Laravel Octane** (가장 쉬운 옵션):

     ```bash
     # FrankenPHP와 함께 Laravel Octane 설치 및 설정 (권장)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Octane 서버 시작
     php artisan octane:start
     ```

     > **중요**: Laravel Octane을 설치할 때 FrankenPHP를 서버로 사용해야 합니다. SSE 연결과의 호환성 문제로 인해 RoadRunner에서는 패키지가 제대로 작동하지 않을 수 있습니다. 이 RoadRunner 호환성 문제를 해결하는 데 도움을 줄 수 있다면 Pull Request를 제출해 주세요 - 여러분의 기여를 매우 환영합니다!

     자세한 내용은 [Laravel Octane 문서](https://laravel.com/docs/12.x/octane)를 참조하세요

   - **프로덕션급 옵션**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - 사용자 정의 Docker 설정

   * SSE 스트리밍을 적절히 지원하는 모든 웹 서버 (레거시 SSE 제공자에만 필요)

2. Inspector 인터페이스에서 Laravel 서버의 MCP 엔드포인트 URL(예: `http://localhost:8000/mcp`)을 입력하세요. 레거시 SSE 제공자를 사용하는 경우 대신 SSE URL(`http://localhost:8000/mcp/sse`)을 사용하세요.
3. 연결하고 사용 가능한 도구를 시각적으로 탐색하세요

MCP 엔드포인트는 `http://[your-laravel-server]/[default_path]` 패턴을 따릅니다. 여기서 `default_path`는 `config/mcp-server.php` 파일에 정의되어 있습니다.

## 고급 기능

### SSE 어댑터를 사용한 Pub/Sub 아키텍처 (레거시 제공자)

패키지는 어댑터 시스템을 통해 발행/구독(pub/sub) 메시징 패턴을 구현합니다:

1. **발행자 (서버)**: 클라이언트가 `/message` 엔드포인트로 요청을 보내면, 서버는 이러한 요청을 처리하고 구성된 어댑터를 통해 응답을 발행합니다.

2. **메시지 브로커 (어댑터)**: 어댑터(예: Redis)는 고유한 클라이언트 ID로 식별되는 각 클라이언트의 메시지 큐를 유지합니다. 이는 신뢰할 수 있는 비동기 통신 계층을 제공합니다.

3. **구독자 (SSE 연결)**: 장기간 지속되는 SSE 연결은 각각의 클라이언트에 대한 메시지를 구독하고 실시간으로 전달합니다. 이는 레거시 SSE 제공자를 사용할 때만 적용됩니다.

이 아키텍처는 다음을 가능하게 합니다:

- 확장 가능한 실시간 통신
- 일시적인 연결 끊김 중에도 신뢰할 수 있는 메시지 전달
- 여러 동시 클라이언트 연결의 효율적인 처리
- 분산 서버 배포 가능성

### Redis 어댑터 구성

기본 Redis 어댑터는 다음과 같이 구성할 수 있습니다:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Redis 키 접두사
        'connection' => 'default', // database.php의 Redis 연결
        'ttl' => 100,              // 메시지 TTL(초)
    ],
],
```


## README.md 번역

Claude API를 사용하여 이 README를 다른 언어로 번역하려면 (병렬 처리):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

특정 언어만 번역할 수도 있습니다:

```bash
python scripts/translate_readme.py es ko
```

## v2.0.0을 위한 폐기 예정 기능

다음 기능들은 폐기 예정이며 v2.0.0에서 제거될 예정입니다. 코드를 적절히 업데이트해 주세요:

### ToolInterface 변경사항

**v1.3.0부터 폐기 예정:**
- `messageType(): ProcessMessageType` 메서드
- **대체:** `isStreaming(): bool`을 대신 사용
- **마이그레이션 가이드:** HTTP 도구는 `false`, 스트리밍 도구는 `true` 반환
- **자동 마이그레이션:** `php artisan mcp:migrate-tools`를 실행하여 도구 업데이트

**마이그레이션 예시:**

```php
// 기존 방식 (폐기 예정)
public function messageType(): ProcessMessageType
{
    return ProcessMessageType::HTTP;
}

// 새로운 방식 (v1.3.0+)
public function isStreaming(): bool
{
    return false; // HTTP는 false, 스트리밍은 true 사용
}
```

### 제거된 기능

**v1.3.0에서 제거됨:**
- `ProcessMessageType::PROTOCOL` enum case (`ProcessMessageType::HTTP`로 통합됨)

**v2.0.0 계획:**
- `ToolInterface`에서 `messageType()` 메서드 완전 제거
- 모든 도구는 `isStreaming()` 메서드만 구현하면 됨
- 도구 구성 단순화 및 복잡성 감소

## 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.