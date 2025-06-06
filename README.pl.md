<h1 align="center">Laravel MCP Server by OP.GG</h1>

<p align="center">
  Potężny pakiet Laravel do bezproblemowego budowania serwera Model Context Protocol
</p>

<p align="center">
<a href="https://github.com/opgginc/laravel-mcp-server/actions"><img src="https://github.com/opgginc/laravel-mcp-server/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/dt/opgginc/laravel-mcp-server" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/v/opgginc/laravel-mcp-server" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/opgginc/laravel-mcp-server"><img src="https://img.shields.io/packagist/l/opgginc/laravel-mcp-server" alt="License"></a>
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
  <a href="README.pl.md">Polski</a> |
  <a href="README.es.md">Español</a>
</p>

## ⚠️ Zmiany łamiące kompatybilność w v1.1.0

Wersja 1.1.0 wprowadziła znaczącą i łamiącą kompatybilność zmianę w `ToolInterface`. Jeśli aktualizujesz z v1.0.x, **musisz** zaktualizować swoje implementacje narzędzi, aby były zgodne z nowym interfejsem.

**Kluczowe zmiany w `ToolInterface`:**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` został zaktualizowany w następujący sposób:

1.  **Dodana nowa metoda:**

    - `messageType(): ProcessMessageType`
      - Ta metoda jest kluczowa dla nowego wsparcia HTTP stream i określa typ przetwarzanej wiadomości.

2.  **Zmiana nazw metod:**
    - `getName()` to teraz `name()`
    - `getDescription()` to teraz `description()`
    - `getInputSchema()` to teraz `inputSchema()`
    - `getAnnotations()` to teraz `annotations()`

**Jak zaktualizować swoje narzędzia:**

### Automatyczna migracja narzędzi dla v1.1.0

Aby pomóc w przejściu na nowy `ToolInterface` wprowadzony w v1.1.0, dołączyliśmy komendę Artisan, która może pomóc zautomatyzować refaktoryzację istniejących narzędzi:

```bash
php artisan mcp:migrate-tools {path?}
```

**Co robi:**

Ta komenda przeskanuje pliki PHP w określonym katalogu (domyślnie `app/MCP/Tools/`) i spróbuje:

1.  **Zidentyfikować stare narzędzia:** Szuka klas implementujących `ToolInterface` ze starymi sygnaturami metod.
2.  **Utworzyć kopie zapasowe:** Przed wprowadzeniem jakichkolwiek zmian utworzy kopię zapasową oryginalnego pliku narzędzia z rozszerzeniem `.backup` (np. `YourTool.php.backup`). Jeśli plik kopii zapasowej już istnieje, oryginalny plik zostanie pominięty, aby zapobiec przypadkowej utracie danych.
3.  **Zrefaktoryzować narzędzie:**
    - Zmienić nazwy metod:
      - `getName()` na `name()`
      - `getDescription()` na `description()`
      - `getInputSchema()` na `inputSchema()`
      - `getAnnotations()` na `annotations()`
    - Dodać nową metodę `messageType()`, która domyślnie będzie zwracać `ProcessMessageType::SSE`.
    - Upewnić się, że instrukcja `use OPGG\LaravelMcpServer\Enums\ProcessMessageType;` jest obecna.

**Użycie:**

Po aktualizacji pakietu `opgginc/laravel-mcp-server` do v1.1.0 lub nowszej, jeśli masz istniejące narzędzia napisane dla v1.0.x, zdecydowanie zaleca się uruchomienie tej komendy:

```bash
php artisan mcp:migrate-tools
```

Jeśli twoje narzędzia znajdują się w katalogu innym niż `app/MCP/Tools/`, możesz określić ścieżkę:

```bash
php artisan mcp:migrate-tools path/to/your/tools
```

Komenda będzie wyświetlać swój postęp, wskazując które pliki są przetwarzane, kopiowane i migrowane. Zawsze przejrzyj zmiany wprowadzone przez narzędzie. Choć ma na celu być dokładne, złożone lub nietypowo sformatowane pliki narzędzi mogą wymagać ręcznych poprawek.

To narzędzie powinno znacznie ułatwić proces migracji i pomóc ci szybko dostosować się do nowej struktury interfejsu.

### Migracja ręczna

Jeśli wolisz migrować swoje narzędzia ręcznie, oto porównanie, które pomoże ci dostosować istniejące narzędzia:

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

**`ToolInterface` v1.1.0 (Nowy):**

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    public function messageType(): ProcessMessageType; // Nowa metoda
    public function name(): string;                     // Zmieniona nazwa
    public function description(): string;              // Zmieniona nazwa
    public function inputSchema(): array;               // Zmieniona nazwa
    public function annotations(): array;               // Zmieniona nazwa
    public function execute(array $arguments): mixed;   // Bez zmian
}
```

**Przykład zaktualizowanego narzędzia:**

Jeśli twoje narzędzie v1.0.x wyglądało tak:

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

Musisz je zaktualizować dla v1.1.0 w następujący sposób:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType; // Importuj enum

class MyNewTool implements ToolInterface
{
    // Dodaj nową metodę messageType()
    public function messageType(): ProcessMessageType
    {
        // Zwróć odpowiedni typ wiadomości, np. dla standardowego narzędzia
        return ProcessMessageType::SSE;
    }

    public function name(): string { return 'MyNewTool'; } // Zmieniona nazwa
    public function description(): string { return 'This is my new tool.'; } // Zmieniona nazwa
    public function inputSchema(): array { return []; } // Zmieniona nazwa
    public function annotations(): array { return []; } // Zmieniona nazwa
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Przegląd Laravel MCP Server

Laravel MCP Server to potężny pakiet zaprojektowany do usprawnienia implementacji serwerów Model Context Protocol (MCP) w aplikacjach Laravel. **W przeciwieństwie do większości pakietów Laravel MCP, które używają transportu Standard Input/Output (stdio)**, ten pakiet skupia się na transporcie **Streamable HTTP** i nadal zawiera **legacy SSE provider** dla kompatybilności wstecznej, zapewniając bezpieczną i kontrolowaną metodę integracji.

### Dlaczego Streamable HTTP zamiast STDIO?

Choć stdio jest proste i szeroko używane w implementacjach MCP, ma znaczące implikacje bezpieczeństwa dla środowisk korporacyjnych:

- **Ryzyko bezpieczeństwa**: Transport STDIO potencjalnie ujawnia szczegóły wewnętrznego systemu i specyfikacje API
- **Ochrona danych**: Organizacje muszą chronić zastrzeżone endpointy API i wewnętrzną architekturę systemu
- **Kontrola**: Streamable HTTP oferuje lepszą kontrolę nad kanałem komunikacji między klientami LLM a twoją aplikacją

Implementując serwer MCP z transportem Streamable HTTP, przedsiębiorstwa mogą:

- Ujawnić tylko niezbędne narzędzia i zasoby, zachowując prywatność zastrzeżonych szczegółów API
- Utrzymać kontrolę nad procesami uwierzytelniania i autoryzacji

Kluczowe korzyści:

- Bezproblemowa i szybka implementacja Streamable HTTP w istniejących projektach Laravel
- Wsparcie dla najnowszych wersji Laravel i PHP
- Efektywna komunikacja serwera i przetwarzanie danych w czasie rzeczywistym
- Zwiększone bezpieczeństwo dla środowisk korporacyjnych

## Kluczowe funkcje

- Wsparcie komunikacji w czasie rzeczywistym przez Streamable HTTP z integracją SSE
- Implementacja narzędzi i zasobów zgodnych ze specyfikacjami Model Context Protocol
- Architektura oparta na adapterach z wzorcem komunikatów Pub/Sub (zaczynając od Redis, planowane są kolejne adaptery)
- Prosta konfiguracja routingu i middleware

### Dostawcy transportu

Opcja konfiguracji `server_provider` kontroluje, który transport jest używany. Dostępni dostawcy to:

1. **streamable_http** – zalecany domyślny. Używa standardowych żądań HTTP i unika problemów z platformami, które zamykają połączenia SSE po około minucie (np. wiele środowisk serverless).
2. **sse** – legacy provider zachowany dla kompatybilności wstecznej. Polega na długotrwałych połączeniach SSE i może nie działać na platformach z krótkimi timeoutami HTTP.

Protokół MCP definiuje również tryb "Streamable HTTP SSE", ale ten pakiet go nie implementuje i nie ma planów, aby to zrobić.

## Wymagania

- PHP >=8.2
- Laravel >=10.x

## Instalacja

1. Zainstaluj pakiet przez Composer:

   ```bash
   composer require opgginc/laravel-mcp-server
   ```

2. Opublikuj plik konfiguracji:
   ```bash
   php artisan vendor:publish --provider="OPGG\LaravelMcpServer\LaravelMcpServerServiceProvider"
   ```

## Podstawowe użycie

### Tworzenie i dodawanie niestandardowych narzędzi

Pakiet zapewnia wygodne komendy Artisan do generowania nowych narzędzi:

```bash
php artisan make:mcp-tool MyCustomTool
```

Ta komenda:

- Obsługuje różne formaty wejściowe (spacje, myślniki, mieszane wielkości liter)
- Automatycznie konwertuje nazwę do odpowiedniego formatu
- Tworzy prawidłowo ustrukturyzowaną klasę narzędzia w `app/MCP/Tools`
- Oferuje automatyczną rejestrację narzędzia w twojej konfiguracji

Możesz również ręcznie tworzyć i rejestrować narzędzia w `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementacja narzędzia
}
```

### Zrozumienie struktury twojego narzędzia (ToolInterface)

Kiedy tworzysz narzędzie implementując `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, musisz zdefiniować kilka metod. Oto rozbicie każdej metody i jej przeznaczenia:

```php
<?php

namespace OPGG\LaravelMcpServer\Services\ToolService;

use OPGG\LaravelMcpServer\Enums\ProcessMessageType;

interface ToolInterface
{
    // Określa, jak wiadomości narzędzia są przetwarzane, często związane z transportem.
    public function messageType(): ProcessMessageType;

    // Unikalna, wywoływalna nazwa twojego narzędzia (np. 'get-user-details').
    public function name(): string;

    // Czytelny dla człowieka opis tego, co robi twoje narzędzie.
    public function description(): string;

    // Definiuje oczekiwane parametry wejściowe dla twojego narzędzia używając struktury podobnej do JSON Schema.
    public function inputSchema(): array;

    // Zapewnia sposób dodawania dowolnych metadanych lub adnotacji do twojego narzędzia.
    public function annotations(): array;

    // Główna logika twojego narzędzia. Otrzymuje zwalidowane argumenty i zwraca wynik.
    public function execute(array $arguments): mixed;
}
```

Zagłębmy się w niektóre z tych metod:

**`messageType(): ProcessMessageType`**

Ta metoda określa typ przetwarzania wiadomości dla twojego narzędzia. Zwraca wartość enum `ProcessMessageType`. Dostępne typy to:

- `ProcessMessageType::HTTP`: Dla narzędzi współpracujących przez standardowe żądanie/odpowiedź HTTP. Najczęściej używane dla nowych narzędzi.
- `ProcessMessageType::SSE`: Dla narzędzi specjalnie zaprojektowanych do pracy z Server-Sent Events.

Dla większości narzędzi, szczególnie tych zaprojektowanych dla głównego dostawcy `streamable_http`, zwrócisz `ProcessMessageType::HTTP`.

**`name(): string`**

To jest identyfikator twojego narzędzia. Powinien być unikalny. Klienci będą używać tej nazwy do żądania twojego narzędzia. Na przykład: `get-weather`, `calculate-sum`.

**`description(): string`**

Jasny, zwięzły opis funkcjonalności twojego narzędzia. Jest używany w dokumentacji, a interfejsy klientów MCP (jak MCP Inspector) mogą go wyświetlać użytkownikom.

**`inputSchema(): array`**

Ta metoda jest kluczowa dla definiowania oczekiwanych parametrów wejściowych twojego narzędzia. Powinna zwracać tablicę, która podąża za strukturą podobną do JSON Schema. Ten schemat jest używany:

- Przez klientów do zrozumienia, jakie dane wysłać.
- Potencjalnie przez serwer lub klienta do walidacji wejścia.
- Przez narzędzia jak MCP Inspector do generowania formularzy do testowania.

**Przykład `inputSchema()`:**

```php
public function inputSchema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'userId' => [
                'type' => 'integer',
                'description' => 'Unikalny identyfikator użytkownika.',
            ],
            'includeDetails' => [
                'type' => 'boolean',
                'description' => 'Czy dołączyć rozszerzone szczegóły w odpowiedzi.',
                'default' => false, // Możesz określić wartości domyślne
            ],
        ],
        'required' => ['userId'], // Określa, które właściwości są obowiązkowe
    ];
}
```

W twojej metodzie `execute` możesz następnie walidować przychodzące argumenty. Przykład `HelloWorldTool` używa `Illuminate\Support\Facades\Validator` do tego:

```php
// Wewnątrz twojej metody execute():
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
// Kontynuuj ze zwalidowanymi $arguments['userId'] i $arguments['includeDetails']
```

**`annotations(): array`**

Ta metoda zapewnia metadane o zachowaniu i charakterystykach twojego narzędzia, podążając za oficjalną [specyfikacją MCP Tool Annotations](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Adnotacje pomagają klientom MCP kategoryzować narzędzia, podejmować świadome decyzje o zatwierdzaniu narzędzi i zapewniać odpowiednie interfejsy użytkownika.

**Standardowe adnotacje MCP:**

Model Context Protocol definiuje kilka standardowych adnotacji, które klienci rozumieją:

- **`title`** (string): Czytelny dla człowieka tytuł narzędzia, wyświetlany w interfejsach klientów
- **`readOnlyHint`** (boolean): Wskazuje, czy narzędzie tylko odczytuje dane bez modyfikowania środowiska (domyślnie: false)
- **`destructiveHint`** (boolean): Sugeruje, czy narzędzie może wykonywać destrukcyjne operacje jak usuwanie danych (domyślnie: true)
- **`idempotentHint`** (boolean): Wskazuje, czy powtarzające się wywołania z tymi samymi argumentami nie mają dodatkowego efektu (domyślnie: false)
- **`openWorldHint`** (boolean): Sygnalizuje, czy narzędzie współdziała z zewnętrznymi podmiotami poza lokalnym środowiskiem (domyślnie: true)

**Ważne:** To są wskazówki, nie gwarancje. Pomagają klientom zapewniać lepsze doświadczenia użytkownika, ale nie powinny być używane do decyzji krytycznych dla bezpieczeństwa.

**Przykład ze standardowymi adnotacjami MCP:**

```php
public function annotations(): array
{
    return [
        'title' => 'Pobieracz profilu użytkownika',
        'readOnlyHint' => true,        // Narzędzie tylko odczytuje dane użytkownika
        'destructiveHint' => false,    // Narzędzie nie usuwa ani nie modyfikuje danych
        'idempotentHint' => true,      // Bezpieczne do wielokrotnego wywoływania
        'openWorldHint' => false,      // Narzędzie dostępuje tylko lokalną bazę danych
    ];
}
```

**Przykłady z życia wzięte według typu narzędzia:**

```php
// Narzędzie zapytań do bazy danych
public function annotations(): array
{
    return [
        'title' => 'Narzędzie zapytań do bazy danych',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => false,
    ];
}

// Narzędzie usuwania postów
public function annotations(): array
{
    return [
        'title' => 'Narzędzie usuwania postów bloga',
        'readOnlyHint' => false,
        'destructiveHint' => true,     // Może usuwać posty
        'idempotentHint' => false,     // Dwukrotne usuwanie ma różne efekty
        'openWorldHint' => false,
    ];
}

// Narzędzie integracji API
public function annotations(): array
{
    return [
        'title' => 'API pogody',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Dostępuje zewnętrzne API pogody
    ];
}
```

**Niestandardowe adnotacje** mogą być również dodane dla twoich specyficznych potrzeb aplikacji:

```php
public function annotations(): array
{
    return [
        // Standardowe adnotacje MCP
        'title' => 'Niestandardowe narzędzie',
        'readOnlyHint' => true,

        // Niestandardowe adnotacje dla twojej aplikacji
        'category' => 'analiza-danych',
        'version' => '2.1.0',
        'author' => 'Zespół danych',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Testowanie narzędzi MCP

Pakiet zawiera specjalną komendę do testowania twoich narzędzi MCP bez potrzeby prawdziwego klienta MCP:

```bash
# Testuj konkretne narzędzie interaktywnie
php artisan mcp:test-tool MyCustomTool

# Wylistuj wszystkie dostępne narzędzia
php artisan mcp:test-tool --list

# Testuj z konkretnym wejściem JSON
php artisan mcp:test-tool MyCustomTool --input='{"param":"value"}'
```

To pomaga ci szybko rozwijać i debugować narzędzia przez:

- Pokazywanie schematu wejściowego narzędzia i walidację wejść
- Wykonywanie narzędzia z twoim podanym wejściem
- Wyświetlanie sformatowanych wyników lub szczegółowych informacji o błędach
- Wsparcie złożonych typów wejściowych włączając obiekty i tablice

### Wizualizacja narzędzi MCP z Inspectorem

Możesz również użyć Model Context Protocol Inspector do wizualizacji i testowania swoich narzędzi MCP:

```bash
# Uruchom MCP Inspector bez instalacji
npx @modelcontextprotocol/inspector node build/index.js
```

To zazwyczaj otworzy interfejs webowy pod `localhost:6274`. Aby przetestować swój serwer MCP:

1. **Ostrzeżenie**: `php artisan serve` NIE MOŻE być używane z tym pakietem, ponieważ nie może obsługiwać wielu połączeń PHP jednocześnie. Ponieważ MCP SSE wymaga przetwarzania wielu połączeń równocześnie, musisz użyć jednej z tych alternatyw:

   - **Laravel Octane** (Najłatwiejsza opcja):

     ```bash
     # Zainstaluj i skonfiguruj Laravel Octane z FrankenPHP (zalecane)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Uruchom serwer Octane
     php artisan octane:start
     ```

     > **Ważne**: Podczas instalacji Laravel Octane upewnij się, że używasz FrankenPHP jako serwera. Pakiet może nie działać poprawnie z RoadRunner z powodu problemów kompatybilności z połączeniami SSE. Jeśli możesz pomóc naprawić ten problem kompatybilności z RoadRunner, proszę prześlij Pull Request - twój wkład byłby bardzo doceniony!

     Szczegóły znajdziesz w [dokumentacji Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opcje produkcyjne**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Niestandardowa konfiguracja Docker

   * Każdy serwer webowy, który prawidłowo wspiera streaming SSE (wymagane tylko dla legacy SSE provider)

2. W interfejsie Inspectora wprowadź URL endpointu MCP twojego serwera Laravel (np. `http://localhost:8000/mcp`). Jeśli używasz legacy SSE provider, użyj zamiast tego URL SSE (`http://localhost:8000/mcp/sse`).
3. Połącz się i eksploruj dostępne narzędzia wizualnie

Endpoint MCP podąża za wzorcem: `http://[twój-serwer-laravel]/[default_path]` gdzie `default_path` jest zdefiniowane w twoim pliku `config/mcp-server.php`.

## Zaawansowane funkcje

### Architektura Pub/Sub z adapterami SSE (legacy provider)

Pakiet implementuje wzorzec komunikatów publish/subscribe (pub/sub) przez swój system adapterów:

1. **Publisher (Serwer)**: Kiedy klienci wysyłają żądania do endpointu `/message`, serwer przetwarza te żądania i publikuje odpowiedzi przez skonfigurowany adapter.

2. **Message Broker (Adapter)**: Adapter (np. Redis) utrzymuje kolejki wiadomości dla każdego klienta, identyfikowane przez unikalne ID klientów. To zapewnia niezawodną asynchroniczną warstwę komunikacji.

3. **Subscriber (połączenie SSE)**: Długotrwałe połączenia SSE subskrybują wiadomości dla swoich odpowiednich klientów i dostarczają je w czasie rzeczywistym. To dotyczy tylko używania legacy SSE provider.

Ta architektura umożliwia:

- Skalowalną komunikację w czasie rzeczywistym
- Niezawodne dostarczanie wiadomości nawet podczas tymczasowych rozłączeń
- Efektywną obsługę wielu równoczesnych połączeń klientów
- Potencjał dla rozproszonych wdrożeń serwera

### Konfiguracja adaptera Redis

Domyślny adapter Redis może być skonfigurowany w następujący sposób:

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

Pakiet wspiera następujące zmienne środowiskowe, aby umożliwić konfigurację bez modyfikowania plików konfiguracyjnych:

| Zmienna                | Opis                                    | Domyślna |
| ---------------------- | --------------------------------------- | -------- |
| `MCP_SERVER_ENABLED`   | Włącz lub wyłącz serwer MCP             | `true`   |

### Przykład konfiguracji .env

```
# Wyłącz serwer MCP w określonych środowiskach
MCP_SERVER_ENABLED=false
```

## Tłumaczenie README.md

Aby przetłumaczyć ten README na inne języki używając Claude API (przetwarzanie równoległe):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Możesz również tłumaczyć konkretne języki:

```bash
python scripts/translate_readme.py es ko
```

## Licencja

Ten projekt jest dystrybuowany na licencji MIT.
