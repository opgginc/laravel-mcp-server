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

## ⚠️ v1.1.0의 Breaking Changes

버전 1.1.0에서는 `ToolInterface`에 중요한 변경사항이 도입되었습니다. v1.0.x에서 업그레이드하는 경우, 새로운 인터페이스에 맞게 도구 구현을 **반드시** 업데이트해야 합니다.

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

### v1.1.0 자동 도구 마이그레이션

v1.1.0에서 도입된 새로운 `ToolInterface`로의 전환을 돕기 위해, 기존 도구의 리팩토링을 자동화할 수 있는 Artisan 명령어를 포함했습니다:

```bash
php artisan mcp:migrate-tools {path?}
```

**기능:**

이 명령어는 지정된 디렉토리(기본값: `app/MCP/Tools/`)의 PHP 파일을 스캔하여 다음을 수행합니다:

1.  **기존 도구 식별:** 이전 메서드 시그니처로 `ToolInterface`를 구현하는 클래스를 찾습니다.
2.  **백업 생성:** 변경하기 전에 원본 도구 파일의 백업을 `.backup` 확장자로 생성합니다(예: `YourTool.php.backup`). 백업 파일이 이미 존재하면 실수로 데이터가 손실되는 것을 방지하기 위해 원본 파일을 건너뜁니다.
3.  **도구 리팩토링:**
    - 메서드 이름 변경:
      - `getName()`을 `name()`으로
      - `getDescription()`을 `description()`으로
      - `getInputSchema()`를 `inputSchema()`로
      - `getAnnotations()`를 `annotations()`로
    - 새로운 `messageType()` 메서드 추가, 기본값으로 `ProcessMessageType::SSE`를 반환합니다.
    - `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` 구문이 있는지 확인합니다.

**사용법:**

`opgginc/laravel-mcp-server` 패키지를 v1.1.0 이상으로 업데이트한 후, v1.0.x용으로 작성된 기존 도구가 있다면 이 명령어를 실행하는 것을 강력히 권장합니다:

```bash
php artisan mcp:migrate-tools
```

도구가 `app/MCP/Tools/` 이외의 디렉토리에 있다면 경로를 지정할 수 있습니다:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

명령어는 진행 상황을 출력하여 어떤 파일이 처리되고, 백업되고, 마이그레이션되는지 알려줍니다. 도구가 만든 변경사항을 항상 검토하세요. 정확성을 목표로 하지만, 복잡하거나 비정상적으로 포맷된 도구 파일은 수동 조정이 필요할 수 있습니다.

이 도구는 마이그레이션 과정을 크게 간소화하고 새로운 인터페이스 구조에 빠르게 적응할 수 있도록 도와줍니다.

### 수동 마이그레이션

도구를 수동으로 마이그레이션하려면, 다음 비교를 통해 기존 도구를 적응시킬 수 있습니다:

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

**v1.1.0 `ToolInterface` (새로운 버전):**

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

v1.1.0용으로 다음과 같이 업데이트해야 합니다:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // enum 임포트

class MyNewTool implements ToolInterface
{
    // 새로운 messageType() 메서드 추가
    public function messageType(): ProcessMessageType
    {
        // 적절한 메시지 타입 반환, 예: 표준 도구의 경우
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // 이름 변경
    public function description(): string { return 'This is my new tool.'; } // 이름 변경
    public function inputSchema(): array { return []; } // 이름 변경
    public function annotations(): array { return []; } // 이름 변경
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Laravel MCP Server 개요

Laravel MCP Server는 Laravel 애플리케이션에서 Model Context Protocol (MCP) 서버 구현을 간소화하도록 설계된 강력한 패키지입니다. **Standard Input/Output (stdio) 전송을 사용하는 대부분의 Laravel MCP 패키지와 달리**, 이 패키지는 **Streamable HTTP** 전송에 중점을 두고 있으며 하위 호환성을 위한 **레거시 SSE 프로바이더**도 포함하여 안전하고 제어된 통합 방법을 제공합니다.

### STDIO 대신 Streamable HTTP를 사용하는 이유?

stdio는 간단하고 MCP 구현에서 널리 사용되지만, 기업 환경에서는 심각한 보안 문제가 있습니다:

- **보안 위험**: STDIO 전송은 잠재적으로 내부 시스템 세부사항과 API 사양을 노출할 수 있습니다
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

### 전송 프로바이더

구성 옵션 `server_provider`는 사용되는 전송을 제어합니다. 사용 가능한 프로바이더는 다음과 같습니다:

1. **streamable_http** – 권장되는 기본값입니다. 표준 HTTP 요청을 사용하고 약 1분 후 SSE 연결을 닫는 플랫폼(예: 많은 서버리스 환경)에서 발생하는 문제를 방지합니다.
2. **sse** – 하위 호환성을 위해 유지되는 레거시 프로바이더입니다. 장기간 SSE 연결에 의존하며 짧은 HTTP 타임아웃이 있는 플랫폼에서는 작동하지 않을 수 있습니다.

MCP 프로토콜은 "Streamable HTTP SSE" 모드도 정의하지만, 이 패키지는 이를 구현하지 않으며 구현할 계획도 없습니다.

## 요구사항

- PHP >=8.2
- Laravel >=10.x

## 설치

1. Composer를 통해 패키지를 설치하세요:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 구성 파일을 게시하세요:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 기본 사용법

### 커스텀 도구 생성 및 추가

패키지는 새로운 도구를 생성하는 편리한 Artisan 명령어를 제공합니다:

```bash
php artisan make:mcp-tool MyCustomTool
```

이 명령어는:

- 다양한 입력 형식(공백, 하이픈, 대소문자 혼합)을 처리합니다
- 이름을 자동으로 적절한 케이스 형식으로 변환합니다
- `app/MCP/Tools`에 적절히 구조화된 도구 클래스를 생성합니다
- 구성에서 도구를 자동으로 등록할 것인지 제안합니다

`config/mcp-server.php`에서 도구를 수동으로 생성하고 등록할 수도 있습니다:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 도구 구현
}
```

### 도구 구조 이해하기 (ToolInterface)

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`를 구현하여 도구를 생성할 때, 여러 메서드를 정의해야 합니다. 각 메서드와 그 목적에 대한 분석은 다음과 같습니다:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    // 도구의 메시지가 어떻게 처리되는지 결정하며, 종종 전송과 관련됩니다.
    public function messageType(): ProcessMessageType;

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

**`messageType(): ProcessMessageType`**

이 메서드는 도구의 메시지 처리 유형을 지정합니다. `ProcessMessageType` enum 값을 반환합니다. 사용 가능한 유형은 다음과 같습니다:

- `ProcessMessageType::HTTP`: 표준 HTTP 요청/응답을 통해 상호작용하는 도구용. 새로운 도구에서 가장 일반적입니다.
- `ProcessMessageType::SSE`: Server-Sent Events와 함께 작동하도록 특별히 설계된 도구용.

대부분의 도구, 특히 기본 `streamable_http` 프로바이더용으로 설계된 도구의 경우 `ProcessMessageType::HTTP`를 반환합니다.

**`name(): string`**

이것은 도구의 식별자입니다. 고유해야 합니다. 클라이언트는 이 이름을 사용하여 도구를 요청합니다. 예: `get-weather`, `calculate-sum`.

**`description(): string`**

도구 기능에 대한 명확하고 간결한 설명입니다. 이는 문서화에 사용되며, MCP 클라이언트 UI(예: MCP Inspector)에서 사용자에게 표시할 수 있습니다.

**`inputSchema(): array`**

이 메서드는 도구의 예상 입력 매개변수를 정의하는 데 중요합니다. JSON Schema와 유사한 구조를 따르는 배열을 반환해야 합니다. 이 스키마는 다음에 사용됩니다:

- 클라이언트가 전송할 데이터를 이해하기 위해.
- 서버나 클라이언트에서 입력 검증을 위해 잠재적으로 사용.
- MCP Inspector와 같은 도구에서 테스트용 폼을 생성하기 위해.

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
// execute() 메서드 내부에서:
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

이 메서드는 공식 [MCP Tool Annotations 사양](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations)에 따라 도구의 동작과 특성에 대한 메타데이터를 제공합니다. 주석은 MCP 클라이언트가 도구를 분류하고, 도구 승인에 대한 정보에 기반한 결정을 내리며, 적절한 사용자 인터페이스를 제공하는 데 도움이 됩니다.

**표준 MCP 주석:**

Model Context Protocol은 클라이언트가 이해하는 여러 표준 주석을 정의합니다:

- **`title`** (string): 클라이언트 UI에 표시되는 도구의 사람이 읽을 수 있는 제목
- **`readOnlyHint`** (boolean): 도구가 환경을 수정하지 않고 데이터만 읽는지 나타냅니다 (기본값: false)
- **`destructiveHint`** (boolean): 도구가 데이터 삭제와 같은 파괴적인 작업을 수행할 수 있는지 제안합니다 (기본값: true)
- **`idempotentHint`** (boolean): 동일한 인수로 반복 호출해도 추가 효과가 없는지 나타냅니다 (기본값: false)
- **`openWorldHint`** (boolean): 도구가 로컬 환경을 넘어 외부 엔티티와 상호작용하는지 신호를 보냅니다 (기본값: true)

**중요:** 이것들은 힌트이지 보장이 아닙니다. 클라이언트가 더 나은 사용자 경험을 제공하는 데 도움이 되지만 보안이 중요한 결정에는 사용해서는 안 됩니다.

**표준 MCP 주석이 있는 예시:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
        'readOnlyHint' => true,        // 도구는 사용자 데이터만 읽습니다
        'destructiveHint' => false,    // 도구는 데이터를 삭제하거나 수정하지 않습니다
        'idempotentHint' => true,      // 여러 번 호출해도 안전합니다
        'openWorldHint' => false,      // 도구는 로컬 데이터베이스에만 액세스합니다
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
        'destructiveHint' => true,     // 게시물을 삭제할 수 있습니다
        'idempotentHint' => false,     // 두 번 삭제하면 다른 효과가 있습니다
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
        'openWorldHint' => true,       // 외부 날씨 API에 액세스합니다
    ];
}
```

**커스텀 주석**도 특정 애플리케이션 요구사항에 맞게 추가할 수 있습니다:

```php
public function annotations(): array
{
    return [
        // 표준 MCP 주석
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // 애플리케이션용 커스텀 주석
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

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

- 도구의 입력 스키마를 보여주고 입력을 검증
- 제공된 입력으로 도구를 실행
- 포맷된 결과나 자세한 오류 정보 표시
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
     - 커스텀 Docker 설정

   * SSE 스트리밍을 적절히 지원하는 모든 웹 서버 (레거시 SSE 프로바이더에만 필요)

2. Inspector 인터페이스에서 Laravel 서버의 MCP 엔드포인트 URL(예: `http://localhost:8000/mcp`)을 입력하세요. 레거시 SSE 프로바이더를 사용하는 경우 대신 SSE URL(`http://localhost:8000/mcp/sse`)을 사용하세요.
3. 연결하고 사용 가능한 도구를 시각적으로 탐색하세요

MCP 엔드포인트는 다음 패턴을 따릅니다: `http://[your-laravel-server]/[default_path]` 여기서 `default_path`는 `config/mcp-server.php` 파일에 정의되어 있습니다.

## 고급 기능

### SSE 어댑터를 사용한 Pub/Sub 아키텍처 (레거시 프로바이더)

패키지는 어댑터 시스템을 통해 publish/subscribe (pub/sub) 메시징 패턴을 구현합니다:

1. **Publisher (서버)**: 클라이언트가 `/message` 엔드포인트로 요청을 보내면, 서버는 이러한 요청을 처리하고 구성된 어댑터를 통해 응답을 게시합니다.

2. **Message Broker (어댑터)**: 어댑터(예: Redis)는 고유한 클라이언트 ID로 식별되는 각 클라이언트의 메시지 큐를 유지합니다. 이는 신뢰할 수 있는 비동기 통신 계층을 제공합니다.

3. **Subscriber (SSE 연결)**: 장기간 SSE 연결은 각각의 클라이언트에 대한 메시지를 구독하고 실시간으로 전달합니다. 이는 레거시 SSE 프로바이더를 사용할 때만 적용됩니다.

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
        'prefix' => 'mcp_sse_',    // Redis 키의 접두사
        'connection' => 'default', // database.php의 Redis 연결
        'ttl' => 100,              // 메시지 TTL(초)
    ],
],
```

## 환경 변수

패키지는 구성 파일을 수정하지 않고도 구성할 수 있도록 다음 환경 변수를 지원합니다:

| 변수                   | 설명                                    | 기본값    |
| ---------------------- | --------------------------------------- | --------- |
| `MCP_SERVER_ENABLED`   | MCP 서버 활성화 또는 비활성화           | `true`    |
| `MCP_REDIS_CONNECTION` | database.php의 Redis 연결 이름          | `default` |

### .env 구성 예시

```
# 특정 환경에서 MCP 서버 비활성화
MCP_SERVER_ENABLED=false

# MCP용 특정 Redis 연결 사용
MCP_REDIS_CONNECTION=mcp
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

## 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.