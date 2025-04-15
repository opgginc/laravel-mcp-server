<h1 align="center">Laravel MCP Server od OP.GG</h1>

<p align="center">
  Potężny pakiet Laravel do płynnego budowania serwerów Protokołu Kontekstu Modelu
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Status kompilacji"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Liczba pobrań"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Najnowsza stabilna wersja"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="Licencja"></a>
</p>

<p align="center">
<a href="https://op.gg/open-source/laravel-mcp-server">Oficjalna strona</a>
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

## Przegląd

Laravel MCP Server to potężny pakiet zaprojektowany, aby uprościć implementację serwerów Protokołu Kontekstu Modelu (MCP) w aplikacjach Laravel. **W przeciwieństwie do większości pakietów Laravel MCP, które używają transportu Standardowego Wejścia/Wyjścia (stdio)**, ten pakiet **wykorzystuje transport Server-Sent Events (SSE)**, zapewniając bezpieczniejszą i bardziej kontrolowaną metodę integracji.

### Dlaczego SSE zamiast STDIO?

Chociaż stdio jest proste i szeroko stosowane w implementacjach MCP, ma znaczące implikacje bezpieczeństwa dla środowisk korporacyjnych:

- **Ryzyko bezpieczeństwa**: Transport STDIO potencjalnie ujawnia wewnętrzne szczegóły systemu i specyfikacje API
- **Ochrona danych**: Organizacje muszą chronić własnościowe punkty końcowe API i wewnętrzną architekturę systemu
- **Kontrola**: SSE oferuje lepszą kontrolę nad kanałem komunikacji między klientami LLM a Twoją aplikacją

Implementując serwer MCP z transportem SSE, przedsiębiorstwa mogą:

- Eksponować tylko niezbędne narzędzia i zasoby, zachowując prywatność własnościowych szczegółów API
- Utrzymać kontrolę nad procesami uwierzytelniania i autoryzacji

Kluczowe korzyści:

- Bezproblemowa i szybka implementacja SSE w istniejących projektach Laravel
- Wsparcie dla najnowszych wersji Laravel i PHP
- Wydajna komunikacja serwera i przetwarzanie danych w czasie rzeczywistym
- Zwiększone bezpieczeństwo dla środowisk korporacyjnych

## Główne funkcje

- Wsparcie komunikacji w czasie rzeczywistym dzięki integracji Server-Sent Events (SSE)
- Implementacja narzędzi i zasobów zgodnych ze specyfikacjami Protokołu Kontekstu Modelu
- Architektura oparta na adapterach z wzorcem komunikacji Pub/Sub (zaczynając od Redis, planowane są kolejne adaptery)
- Prosta konfiguracja routingu i middleware

## Wymagania

- PHP >=8.2
- Laravel >=10.x

## Instalacja

1. Zainstaluj pakiet przez Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Opublikuj plik konfiguracyjny:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Podstawowe użycie

### Tworzenie i dodawanie własnych narzędzi

Pakiet dostarcza wygodne komendy Artisan do generowania nowych narzędzi:

```bash
php artisan make:mcp-tool MyCustomTool
```

Ta komenda:

- Obsługuje różne formaty wejściowe (spacje, myślniki, mieszane wielkości liter)
- Automatycznie konwertuje nazwę do odpowiedniego formatu wielkości liter
- Tworzy poprawnie strukturyzowaną klasę narzędzia w `app/MCP/Tools`
- Oferuje automatyczną rejestrację narzędzia w Twojej konfiguracji

Możesz również ręcznie tworzyć i rejestrować narzędzia w `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementacja narzędzia
}
```

### Testowanie narzędzi MCP

Pakiet zawiera specjalną komendę do testowania twoich narzędzi MCP bez potrzeby posiadania prawdziwego klienta MCP:

```bash
# Testuj interaktywnie konkretne narzędzie
php artisan mcp:test-tool MyCustomTool

# Wylistuj wszystkie dostępne narzędzia
php artisan mcp:test-tool --list

# Testuj z określonym wejściem JSON
php artisan mcp:test-tool MyCustomTool --input='{"param":"wartosc"}'
```

Pomaga to szybko rozwijać i debugować narzędzia dzięki:

- Pokazywaniu schematu wejściowego narzędzia i walidacji danych wejściowych
- Wykonywaniu narzędzia z podanymi przez Ciebie danymi wejściowymi
- Wyświetlaniu sformatowanych wyników lub szczegółowych informacji o błędach
- Obsłudze złożonych typów wejściowych, w tym obiektów i tablic

### Wizualizacja narzędzi MCP za pomocą Inspektora

Możesz również użyć Inspektora Protokołu Kontekstu Modelu (Model Context Protocol Inspector) do wizualizacji i testowania swoich narzędzi MCP:

```bash
# Uruchom MCP Inspector bez instalacji
npx @modelcontextprotocol/inspector node build/index.js
```

Zazwyczaj otworzy to interfejs webowy na `localhost:6274`. Aby przetestować swój serwer MCP:

1. Uruchom serwer deweloperski Laravel (np. `php artisan serve`)
2. W interfejsie Inspektora wprowadź adres URL SSE swojego serwera Laravel (np. `http://localhost:8000/mcp/sse`)
3. Połącz się i przeglądaj dostępne narzędzia wizualnie

Adres URL SSE ma następujący format: `http://[twój-serwer-laravel]/[default_path]/sse`, gdzie `default_path` jest zdefiniowany w pliku `config/mcp-server.php`.

## Zaawansowane funkcje

### Architektura Pub/Sub z adapterami SSE

Pakiet implementuje wzorzec komunikacji publikuj/subskrybuj (pub/sub) poprzez system adapterów:

1. **Wydawca (Serwer)**: Gdy klienci wysyłają żądania do punktu końcowego `/message`, serwer przetwarza te żądania i publikuje odpowiedzi przez skonfigurowany adapter.

2. **Broker wiadomości (Adapter)**: Adapter (np. Redis) utrzymuje kolejki wiadomości dla każdego klienta, identyfikowane przez unikalne ID klienta. Zapewnia to niezawodną asynchroniczną warstwę komunikacji.

3. **Subskrybent (Połączenie SSE)**: Długotrwałe połączenia SSE subskrybują wiadomości dla swoich klientów i dostarczają je w czasie rzeczywistym.

Ta architektura umożliwia:

- Skalowalną komunikację w czasie rzeczywistym
- Niezawodne dostarczanie wiadomości nawet podczas tymczasowych rozłączeń
- Wydajną obsługę wielu równoczesnych połączeń klientów
- Potencjał dla rozproszonych wdrożeń serwerowych

### Konfiguracja adaptera Redis

Domyślny adapter Redis można skonfigurować w następujący sposób:

```php
'sse_adapter' => 'redis',
'adapters' => [
    'redis' => [
        'prefix' => 'mcp_sse_',    // Prefiks dla kluczy Redis
        'connection' => 'default', // Połączenie Redis z database.php
        'ttl' => 100,              // TTL wiadomości w sekundach
    ],
],
```

## Zmienne środowiskowe

Pakiet wspiera następujące zmienne środowiskowe, umożliwiające konfigurację bez modyfikowania plików konfiguracyjnych:

| Zmienna | Opis | Domyślna wartość |
|----------|-------------|--------|
| `MCP_SERVER_ENABLED` | Włącz lub wyłącz serwer MCP | `true` |
| `MCP_REDIS_CONNECTION` | Nazwa połączenia Redis z database.php | `default` |

### Przykładowa konfiguracja .env

```
# Wyłącz serwer MCP w określonych środowiskach
MCP_SERVER_ENABLED=false

# Użyj określonego połączenia Redis dla MCP
MCP_REDIS_CONNECTION=mcp
```

## Licencja

Ten projekt jest dystrybuowany na licencji MIT.
