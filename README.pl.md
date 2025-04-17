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

Laravel MCP Server to konkretne narzędzie, które ułatwia stworzenie serwerów MCP w Laravelu. **W odróżnieniu od większości innych pakietów, które bazują na stdio**, ten pakiet **korzysta z SSE (Server-Sent Events)**, co daje Ci większe bezpieczeństwo i kontrolę nad integracją.

### Czemu SSE zamiast STDIO?

Stdio jest proste i popularne w implementacjach MCP, ale w firmowych środowiskach stwarza spore problemy z bezpieczeństwem:

- **Bezpieczeństwo**: STDIO może wyciec poufne szczegóły systemu i API
- **Ochrona danych**: Firmy muszą chronić swoje endpointy API i wewnętrzną architekturę
- **Kontrola**: SSE daje Ci lepszą kontrolę nad komunikacją między klientami LLM a Twoją aplikacją

Z serwerem MCP opartym na SSE możesz:

- Udostępnić tylko potrzebne narzędzia, chroniąc poufne szczegóły API
- Lepiej kontrolować procesy uwierzytelniania i autoryzacji

Główne zalety:

- Szybka i łatwa integracja SSE w istniejących projektach Laravel
- Pełne wsparcie dla najnowszych wersji Laravel i PHP
- Wydajna komunikacja i przetwarzanie danych na żywo
- Lepsze bezpieczeństwo dla środowisk firmowych

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

Masz tu fajną komendę do testowania swoich narzędzi MCP bez potrzeby posiadania klienta MCP:

```bash
# Testuj narzędzie interaktywnie
php artisan mcp:test-tool MyCustomTool

# Zobacz listę wszystkich narzędzi
php artisan mcp:test-tool --list

# Testuj z konkretnym JSON-em
php artisan mcp:test-tool MyCustomTool --input='{"param":"wartosc"}'
```

Dzięki temu możesz szybko rozwijać i debugować narzędzia:

- Widzisz schemat wejściowy i walidację danych
- Testujesz narzędzie z własnymi danymi
- Dostajesz sformatowane wyniki lub szczegółowe błędy
- Obsługujesz złożone dane wejściowe, w tym obiekty i tablice

### Wizualizacja narzędzi MCP z Inspektorem

Możesz też użyć MCP Inspectora do wizualnej pracy z narzędziami:

```bash
# Odpal Inspektora bez instalacji
npx @modelcontextprotocol/inspector node build/index.js
```

To otworzy interfejs w przeglądarce na `localhost:6274`. Żeby przetestować swój serwer:

1. **UWAGA**: NIE MOŻNA używać `php artisan serve` z tym pakietem, ponieważ nie może on obsługiwać wielu połączeń PHP jednocześnie. Ponieważ MCP SSE wymaga przetwarzania wielu połączeń równocześnie, należy użyć jednej z tych alternatyw:

   * **Laravel Octane** (najłatwiejsza opcja):
     ```bash
     # Instalacja i konfiguracja Laravel Octane
     composer require laravel/octane
     php artisan octane:install
     
     # Uruchom serwer Octane
     php artisan octane:start
     ```
     Szczegóły znajdziesz w [dokumentacji Laravel Octane](https://laravel.com/docs/12.x/octane)
     
   * **Opcje produkcyjne**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Własna konfiguracja Docker
     - Dowolny serwer internetowy z prawidłową obsługą streamingu SSE

2. W Inspektorze wklej URL swojego serwera SSE (np. `http://localhost:8000/mcp/sse`)
3. Połącz się i testuj narzędzia wizualnie

Format URL SSE to: `http://[twój-serwer]/[default_path]/sse`, gdzie `default_path` ustawiasz w `config/mcp-server.php`.

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
