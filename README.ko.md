<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
Laravel 및 Lumen에서 경로 우선 MCP 서버 구축
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Official Website</a>
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

## 주요 변경 사항 2.0.0

- 엔드포인트 설정이 구성 기반 등록에서 경로 기반 등록으로 이동되었습니다.
- 스트리밍 가능한 HTTP는 유일하게 지원되는 전송입니다.
- 서버 메타데이터 변경자는 `setServerInfo(...)`로 통합됩니다.
- 레거시 도구 전송 방법이 런타임에서 제거되었습니다(`messageType()`, `ProcessMessageType::SSE`).

전체 마이그레이션 가이드: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## 개요

Laravel MCP 서버는 Laravel 및 Lumen에 대한 경로 기반 MCP 끝점 등록을 제공합니다.

핵심 사항:
- 스트리밍 가능한 HTTP 전송
- 경로 우선 구성(`Route::mcp(...)` / `McpRoute::register(...)`)
- 엔드포인트별 도구, 리소스, 리소스 템플릿 및 프롬프트 등록
- 경로 캐시 호환 엔드포인트 메타데이터

## 요구사항

- PHP >= 8.2
- 라라벨(Illuminate) >= 9.x
- 루멘 >= 9.x(선택 사항)

## 빠른 시작

### 1) 설치

```bash
composer require opgginc/laravel-mcp-server
```

### 2) 엔드포인트 등록(Laravel)

```php
use Illuminate\Support\Facades\Route;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\VersionCheckTool;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
        VersionCheckTool::class,
    ]);
```

### 3) 확인

```bash
php artisan route:list | grep mcp
php artisan mcp:test-tool --list --endpoint=/mcp
```

빠른 JSON-RPC 확인:

```bash
curl -X POST http://localhost:8000/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}'
```

## 루멘 설정

```php
// bootstrap/app.php
$app->withFacades();
$app->withEloquent();
$app->register(OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider::class);
```

```php
use OPGG\LaravelMcpServer\Routing\McpRoute;
use OPGG\LaravelMcpServer\Services\ToolService\Examples\HelloWorldTool;

McpRoute::register('/mcp')
    ->setServerInfo(
        name: 'OP.GG MCP Server',
        version: '2.0.0',
    )
    ->tools([
        HelloWorldTool::class,
    ]);
```

## 최소한의 보안(프로덕션)

MCP 경로 그룹에서 Laravel 미들웨어를 사용하세요.

```php
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth:sanctum',
    'throttle:100,1',
])->group(function (): void {
    Route::mcp('/mcp')
        ->setServerInfo(
            name: 'Secure MCP',
            version: '2.0.0',
        )
        ->tools([
            \App\MCP\Tools\MyCustomTool::class,
        ]);
});
```

## v2.0.0 마이그레이션 노트(v1.0.0부터)

- MCP 엔드포인트 설정이 구성에서 경로 등록으로 이동되었습니다.
- 스트리밍 가능한 HTTP가 유일한 전송입니다.
- 서버 메타데이터 변경자는 `setServerInfo(...)`로 통합됩니다.
- 레거시 서명에 도구 마이그레이션 명령을 사용할 수 있습니다.

```bash
php artisan mcp:migrate-tools
```

전체 가이드: [docs/migrations/v2.0.0-migration.md](docs/migrations/v2.0.0-migration.md)

## 고급 기능(빠른 링크)

- 도구 만들기: `php artisan make:mcp-tool ToolName`
- 리소스 생성: `php artisan make:mcp-resource ResourceName`
- 리소스 템플릿 생성: `php artisan make:mcp-resource-template TemplateName`
- 프롬프트 생성: `php artisan make:mcp-prompt PromptName`
- 알림 만들기: `php artisan make:mcp-notification HandlerName --method=notifications/method`
- OpenAPI에서 생성: `php artisan make:swagger-mcp-tool <spec-url-or-file>`

코드 참조:
- 도구 예: `src/Services/ToolService/Examples/`
- 리소스 예: `src/Services/ResourceService/Examples/`
- 신속한 서비스: `src/Services/PromptService/`
- 알림 처리기: `src/Server/Notification/`
- 경로 빌더: `src/Routing/McpRouteBuilder.php`

## Swagger/OpenAPI -> MCP 도구

Swagger/OpenAPI 사양에서 MCP 도구를 생성합니다.

```bash
# From URL
php artisan make:swagger-mcp-tool https://api.example.com/openapi.json

# From local file
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

유용한 옵션:

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json \
  --group-by=tag \
  --prefix=Billing \
  --test-api
```

- `--group-by`: `tag`, `path` 또는 `none`
- `--prefix`: 생성된 도구/리소스의 클래스 이름 접두사
- `--test-api`: 생성 전 엔드포인트 연결 테스트

생성 동작:
- 대화형 모드에서는 엔드포인트별로 도구 또는 리소스를 선택할 수 있습니다.
- 비대화형 모드에서 `GET` 끝점은 리소스로 생성되고 기타 메서드는 도구로 생성됩니다.

### 향상된 대화형 미리보기

`--group-by` 없이 명령을 실행하면 생성기는 생성 전에 폴더 구조와 파일 수에 대한 대화형 미리 보기를 표시합니다.

```bash
php artisan make:swagger-mcp-tool ./specs/openapi.json
```

미리보기 출력 예:

```text
Choose how to organize your generated tools and resources:

Tag-based grouping (organize by OpenAPI tags)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Pet, Tools/Store, Tools/User

Path-based grouping (organize by API path)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/Api, Tools/Users, Tools/Orders

No grouping (everything in root folder)
  Total: 25 endpoints -> 15 tools + 10 resources
  Examples: Tools/, Resources/
```

생성 후 생성된 도구 클래스를 MCP 엔드포인트에 등록합니다.

```php
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Generated MCP Server',
        version: '2.0.0',
    )
    ->tools([
        \App\MCP\Tools\Billing\CreateInvoiceTool::class,
        \App\MCP\Tools\Billing\UpdateInvoiceTool::class,
    ]);
```

## 예제 도구 클래스

```php
<?php

namespace App\MCP\Tools;

use App\Enums\Platform;
use OPGG\LaravelMcpServer\JsonSchema\JsonSchema;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class GreetingTool implements ToolInterface
{
    public function name(): string
    {
        return 'greeting-tool';
    }

    public function description(): string
    {
        return 'Return a greeting message.';
    }

    public function inputSchema(): array
    {
        return [
            'name' => JsonSchema::string()
                ->description('Developer Name')
                ->required(),
            'platform' => JsonSchema::string()
                ->enum(Platform::class)
                ->description('Client platform')
                ->compact(),
        ];
    }

    public function annotations(): array
    {
        return [
            'readOnlyHint' => true,
            'destructiveHint' => false,
        ];
    }

    public function execute(array $arguments): mixed
    {
        return [
            'message' => 'Hello '.$arguments['name'],
        ];
    }
}
```

## 예제 프롬프트 클래스

```php
<?php

namespace App\MCP\Prompts;

use OPGG\LaravelMcpServer\Services\PromptService\Prompt;

class WelcomePrompt extends Prompt
{
    public string $name = 'welcome-user';

    public ?string $description = 'Generate a welcome message.';

    public array $arguments = [
        [
            'name' => 'username',
            'description' => 'User name',
            'required' => true,
        ],
    ];

    public string $text = 'Welcome, {username}!';
}
```

## 예제 리소스 클래스

```php
<?php

namespace App\MCP\Resources;

use OPGG\LaravelMcpServer\Services\ResourceService\Resource;

class BuildInfoResource extends Resource
{
    public string $uri = 'app://build-info';

    public string $name = 'Build Info';

    public ?string $mimeType = 'application/json';

    public function read(): array
    {
        return [
            'uri' => $this->uri,
            'mimeType' => $this->mimeType,
            'text' => json_encode([
                'version' => '2.0.0',
                'environment' => app()->environment(),
            ], JSON_THROW_ON_ERROR),
        ];
    }
}
```

## 경로에 예제 등록

```php
use App\MCP\Prompts\WelcomePrompt;
use App\MCP\Resources\BuildInfoResource;
use App\MCP\Tools\GreetingTool;
use Illuminate\Support\Facades\Route;

Route::mcp('/mcp')
    ->setServerInfo(
        name: 'Example MCP Server',
        version: '2.0.0',
    )
    ->tools([GreetingTool::class])
    ->resources([BuildInfoResource::class])
    ->prompts([WelcomePrompt::class]);
```

## 테스트 및 품질 명령

```bash
vendor/bin/pest
vendor/bin/phpstan analyse
vendor/bin/pint
```

## 번역

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

선택한 언어 번역:

```bash
python scripts/translate_readme.py es ko
```

## 특허

이 프로젝트는 MIT 라이선스에 따라 배포됩니다.
