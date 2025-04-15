<h1 align="center">OP.GG의 Laravel MCP 서버</h1>

<p align="center">
  Model Context Protocol 서버를 쉽게 구축할 수 있는 강력한 라라벨 패키지
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="빌드 상태"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="총 다운로드 수"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="최신 안정 버전"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="라이센스"></a>
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
  <a href="README.pl.md">Polski</a>
</p>

## 개요

Laravel MCP Server는 라라벨 애플리케이션에서 Model Context Protocol(MCP) 서버를 쉽게 구현할 수 있도록 설계된 강력한 패키지입니다. **대부분의 라라벨 MCP 패키지가 표준 입출력(stdio) 전송 방식을 사용하는 것과 달리**, 이 패키지는 **서버 센트 이벤트(SSE) 전송 방식을 활용**하여 더 안전하고 통제된 통합 방법을 제공합니다.

### 왜 STDIO 대신 SSE인가?

stdio는 간단하고 MCP 구현에 널리 사용되지만, 기업 환경에서는 중요한 보안 문제를 야기할 수 있습니다:

- **보안 위험**: STDIO 전송은 시스템 내부 정보와 API 명세를 노출시킬 가능성이 있음
- **데이터 보호**: 기업은 독점 API 엔드포인트와 내부 시스템 아키텍처를 보호해야 함
- **제어성**: SSE는 LLM 클라이언트와 애플리케이션 간의 통신 채널에 대한 더 나은 제어를 제공함

SSE 전송을 통해 MCP 서버를 구현하면 기업은 다음과 같은 이점을 얻을 수 있습니다:

- 독점 API 세부 정보를 비공개로 유지하면서 필요한 도구와 리소스만 노출
- 인증 및 권한 부여 프로세스에 대한 제어 유지

주요 이점:

- 기존 라라벨 프로젝트에 SSE를 빠르고 쉽게 구현 가능
- 최신 라라벨 및 PHP 버전 지원
- 효율적인 서버 통신 및 실시간 데이터 처리
- 기업 환경을 위한 향상된 보안

## 주요 기능

- 서버 센트 이벤트(SSE) 통합을 통한 실시간 통신 지원
- Model Context Protocol 명세를 준수하는 도구 및 리소스 구현
- Pub/Sub 메시징 패턴이 적용된 어댑터 기반 설계 아키텍처(Redis로 시작, 추가 어댑터 계획 중)
- 간단한 라우팅 및 미들웨어 설정

## 요구 사항

- PHP >=8.2
- Laravel >=10.x

## 설치

1. Composer를 통해 패키지 설치:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. 설정 파일 퍼블리싱:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## 기본 사용법

### 커스텀 도구 생성 및 추가

이 패키지는 새로운 도구를 생성하기 위한 편리한 아티즌 명령어를 제공합니다:

```bash
php artisan make:mcp-tool MyCustomTool
```

이 명령어는:

- 다양한 입력 포맷(공백, 하이픈, 혼합 케이스) 처리
- 이름을 적절한 케이스 포맷으로 자동 변환
- `app/MCP/Tools`에 올바르게 구조화된 도구 클래스 생성
- 설정에 도구를 자동으로 등록할 수 있는 옵션 제공

`config/mcp-server.php`에 수동으로 도구를 생성하고 등록할 수도 있습니다:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // 도구 구현
}
```

### MCP 도구 테스트

이 패키지는 실제 MCP 클라이언트 없이도 MCP 도구를 테스트할 수 있는 특별한 명령어를 포함합니다:

```bash
# 특정 도구를 대화형으로 테스트
php artisan mcp:test-tool MyCustomTool

# 사용 가능한 모든 도구 나열
php artisan mcp:test-tool --list

# 특정 JSON 입력으로 테스트
php artisan mcp:test-tool MyCustomTool --input='{"param":"값"}'
```

이를 통해 다음과 같은 방법으로 도구를 빠르게 개발하고 디버깅할 수 있습니다:

- 도구의 입력 스키마 표시 및 입력 검증
- 제공한 입력으로 도구 실행
- 포맷된 결과 또는 상세 오류 정보 표시
- 객체 및 배열을 포함한 복잡한 입력 타입 지원

### MCP 인스펙터로 도구 시각화하기

Model Context Protocol 인스펙터를 사용하여 MCP 도구를 시각화하고 테스트할 수도 있습니다:

```bash
# 설치 없이 MCP 인스펙터 실행
npx @modelcontextprotocol/inspector node build/index.js
```

이 명령어는 일반적으로 `localhost:6274`에서 웹 인터페이스를 엽니다. MCP 서버를 테스트하려면:

1. Laravel 개발 서버를 시작합니다(예: `php artisan serve`)
2. 인스펙터 인터페이스에서 Laravel 서버의 MCP SSE URL을 입력합니다(예: `http://localhost:8000/mcp/sse`)
3. 연결하고 사용 가능한 도구를 시각적으로 탐색합니다

SSE URL은 `http://[your-laravel-server]/[default_path]/sse` 패턴을 따르며, 여기서 `default_path`는 `config/mcp-server.php` 파일에 정의되어 있습니다.

## 고급 기능

### SSE 어댑터가 적용된 Pub/Sub 아키텍처

이 패키지는 어댑터 시스템을 통해 게시/구독(pub/sub) 메시징 패턴을 구현합니다:

1. **게시자(서버)**: 클라이언트가 `/message` 엔드포인트로 요청을 보내면, 서버는 이 요청을 처리하고 구성된 어댑터를 통해 응답을 게시합니다.

2. **메시지 브로커(어댑터)**: 어댑터(예: Redis)는 고유한 클라이언트 ID로 식별되는 각 클라이언트의 메시지 큐를 유지합니다. 이는 신뢰할 수 있는 비동기 통신 계층을 제공합니다.

3. **구독자(SSE 연결)**: 장기 지속 SSE 연결은 각 클라이언트에 대한 메시지를 구독하고 실시간으로 전달합니다.

이 아키텍처는 다음을 가능하게 합니다:

- 확장 가능한 실시간 통신
- 일시적인 연결 끊김 상황에서도 안정적인 메시지 전달
- 다중 동시 클라이언트 연결의 효율적인 처리
- 분산 서버 배포 가능성

### Redis 어댑터 설정

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

## 환경 변수

이 패키지는 설정 파일을 수정하지 않고도 구성할 수 있도록 다음 환경 변수를 지원합니다:

| 변수 | 설명 | 기본값 |
|----------|-------------|--------|
| `MCP_SERVER_ENABLED` | MCP 서버 활성화 또는 비활성화 | `true` |
| `MCP_REDIS_CONNECTION` | database.php의 Redis 연결 이름 | `default` |

### .env 설정 예시

```
# 특정 환경에서 MCP 서버 비활성화
MCP_SERVER_ENABLED=false

# MCP용 특정 Redis 연결 사용
MCP_REDIS_CONNECTION=mcp
```

## 라이센스

이 프로젝트는 MIT 라이센스 하에 배포됩니다.
