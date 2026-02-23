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

<p align="center">
  <img src="docs/watch.gif" alt="Laravel MCP Server Demo" height="200">
</p>

## ⚠️ Informacje o wersji i zmiany łamiące kompatybilność

### Zmiany w v1.4.0 (Najnowsza) 🚀

Wersja 1.4.0 wprowadza potężną automatyczną generację narzędzi i zasobów ze specyfikacji Swagger/OpenAPI:

**Nowe funkcje:**
- **Generator narzędzi i zasobów Swagger/OpenAPI**: Automatycznie generuje narzędzia lub zasoby MCP z dowolnej specyfikacji Swagger/OpenAPI
  - Obsługuje formaty OpenAPI 3.x i Swagger 2.0
  - **Wybór typu generacji**: Generuj jako Narzędzia (do działań) lub Zasoby (do danych tylko do odczytu)
  - Interaktywny wybór punktów końcowych z opcjami grupowania
  - Automatyczne generowanie logiki uwierzytelniania (API Key, Bearer Token, OAuth2)
  - Inteligentne nazewnictwo dla czytelnych nazw klas (obsługa operationId opartych na hash)
  - Wbudowane testowanie API przed generacją
  - Pełna integracja z klientem HTTP Laravel, w tym logika ponawiania

**Przykład użycia:**
```bash
# Generuj narzędzia z API OP.GG
php artisan make:swagger-mcp-tool https://api.op.gg/lol/swagger.json

# Z opcjami
php artisan make:swagger-mcp-tool ./api-spec.json --test-api --group-by=tag --prefix=MyApi
```

Ta funkcja drastycznie skraca czas potrzebny do integracji zewnętrznych API z Twoim serwerem MCP!

### Zmiany w v1.3.0

Wersja 1.3.0 wprowadza ulepszenia do `ToolInterface` dla lepszej kontroli komunikacji:

**Nowe funkcje:**
- Dodano metodę `isStreaming(): bool` dla jaśniejszego wyboru wzorca komunikacji
- Ulepszone narzędzia migracji obsługujące aktualizacje z v1.1.x, v1.2.x do v1.3.0
- Rozszerzone pliki stub z kompleksową dokumentacją v1.3.0

**Funkcje przestarzałe:**
- Metoda `messageType(): ProcessMessageType` jest teraz przestarzała (zostanie usunięta w v2.0.0)
- Zamiast tego używaj `isStreaming(): bool` dla lepszej przejrzystości i prostoty

### Zmiany łamiące kompatybilność w v1.1.0

Wersja 1.1.0 wprowadziła znaczącą i łamiącą kompatybilność zmianę do `ToolInterface`. Jeśli aktualizujesz z v1.0.x, **musisz** zaktualizować swoje implementacje narzędzi, aby były zgodne z nowym interfejsem.

**Kluczowe zmiany w `ToolInterface`:**

`OPGG\LaravelMcpServer\Services\ToolService\ToolInterface` został zaktualizowany w następujący sposób:

1.  **Dodano nową metodę:**

    - `messageType(): ProcessMessageType`
      - Ta metoda jest kluczowa dla nowego wsparcia strumienia HTTP i określa typ przetwarzanej wiadomości.

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

Komenda będzie wyświetlać swój postęp, wskazując które pliki są przetwarzane, kopiowane i migrowane. Zawsze sprawdź zmiany wprowadzone przez narzędzie. Chociaż ma na celu być dokładne, złożone lub nietypowo sformatowane pliki narzędzi mogą wymagać ręcznych dostosowań.

To narzędzie powinno znacznie ułatwić proces migracji i pomóc ci szybko dostosować się do nowej struktury interfejsu.

### Migracja ręczna

Jeśli wolisz migrować swoje narzędzia ręcznie, oto porównanie, które pomoże ci dostosować istniejące narzędzia:

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

**v1.1.0 `ToolInterface` (Nowy):**

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
    /**
     * @deprecated since v1.3.0, use isStreaming() instead. Will be removed in v2.0.0
     */
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }

    public function isStreaming(): bool
    {
        return false; // Większość narzędzi powinna zwracać false
    }

    public function name(): string { return 'MyNewTool'; }
    public function description(): string { return 'This is my new tool.'; }
    public function inputSchema(): array { return []; }
    public function annotations(): array { return []; }
    public function execute(array $arguments): mixed { /* ... */ }
}
```

## Przegląd Laravel MCP Server

Laravel MCP Server to potężny pakiet zaprojektowany do usprawnienia implementacji serwerów Model Context Protocol (MCP) w aplikacjach Laravel. **W przeciwieństwie do większości pakietów Laravel MCP, które używają transportu Standard Input/Output (stdio)**, ten pakiet skupia się na transporcie **Streamable HTTP** i nadal zawiera **legacy provider SSE** dla kompatybilności wstecznej, zapewniając bezpieczną i kontrolowaną metodę integracji.

### Dlaczego Streamable HTTP zamiast STDIO?

Chociaż stdio jest proste i szeroko używane w implementacjach MCP, ma znaczące implikacje bezpieczeństwa dla środowisk korporacyjnych:

- **Ryzyko bezpieczeństwa**: Transport STDIO potencjalnie ujawnia wewnętrzne szczegóły systemu i specyfikacje API
- **Ochrona danych**: Organizacje muszą chronić zastrzeżone punkty końcowe API i wewnętrzną architekturę systemu
- **Kontrola**: Streamable HTTP oferuje lepszą kontrolę nad kanałem komunikacji między klientami LLM a twoją aplikacją

Implementując serwer MCP z transportem Streamable HTTP, przedsiębiorstwa mogą:

- Ujawnić tylko niezbędne narzędzia i zasoby, zachowując prywatność zastrzeżonych szczegółów API
- Utrzymać kontrolę nad procesami uwierzytelniania i autoryzacji

Kluczowe korzyści:

- Bezproblemowa i szybka implementacja Streamable HTTP w istniejących projektach Laravel
- Wsparcie dla najnowszych wersji Laravel i PHP
- Wydajna komunikacja serwera i przetwarzanie danych w czasie rzeczywistym
- Zwiększone bezpieczeństwo dla środowisk korporacyjnych

## Kluczowe funkcje

- Wsparcie komunikacji w czasie rzeczywistym przez Streamable HTTP z integracją SSE
- Implementacja narzędzi i zasobów zgodnych ze specyfikacjami Model Context Protocol
- Architektura oparta na adapterach z wzorcem komunikatów Pub/Sub (zaczynając od Redis, planowane więcej adapterów)
- Prosta konfiguracja routingu i middleware

### Dostawcy transportu

Opcja konfiguracji `server_provider` kontroluje, który transport jest używany. Dostępni dostawcy to:

1. **streamable_http** – zalecany domyślny. Używa standardowych żądań HTTP i unika problemów z platformami, które zamykają połączenia SSE po około minucie (np. wiele środowisk serverless).
2. **sse** – legacy provider zachowany dla kompatybilności wstecznej. Polega na długotrwałych połączeniach SSE i może nie działać na platformach z krótkimi limitami czasu HTTP.

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


### Tworzenie i dodawanie własnych narzędzi

Pakiet zapewnia wygodne komendy Artisan do generowania nowych narzędzi:

```bash
php artisan make:mcp-tool MyCustomTool
```

Ta komenda:

- Obsługuje różne formaty wejściowe (spacje, myślniki, mieszane wielkości liter)
- Automatycznie konwertuje nazwę do odpowiedniego formatu
- Tworzy prawidłowo ustrukturyzowaną klasę narzędzia w `app/MCP/Tools`
- Oferuje automatyczne zarejestrowanie narzędzia w konfiguracji

Możesz także ręcznie tworzyć i rejestrować narzędzia w `config/mcp-server.php`:

```php
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class MyCustomTool implements ToolInterface
{
    // Implementacja narzędzia
}
```

### Zrozumienie struktury twojego narzędzia (ToolInterface)

Kiedy tworzysz narzędzie implementując `OPGG\LaravelMcpServer\Services\ToolService\ToolInterface`, musisz zdefiniować kilka metod. Oto omówienie każdej metody i jej przeznaczenia:

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

    // NOWE w v1.3.0: Określa, czy to narzędzie wymaga streamingu (SSE) zamiast standardowego HTTP.
    public function isStreaming(): bool;

    // Unikalna, wywoływalna nazwa twojego narzędzia (np. 'get-user-details').
    public function name(): string;

    // Czytelny dla człowieka opis tego, co robi twoje narzędzie.
    public function description(): string;

    // Definiuje oczekiwane parametry wejściowe dla twojego narzędzia używając struktury podobnej do JSON Schema.
    public function inputSchema(): array;

    // Zapewnia sposób dodawania arbitralnych metadanych lub adnotacji do twojego narzędzia.
    public function annotations(): array;

    // Główna logika twojego narzędzia. Otrzymuje zwalidowane argumenty i zwraca wynik.
    public function execute(array $arguments): mixed;
}
```

Zagłębmy się w niektóre z tych metod:

**`messageType(): ProcessMessageType` (Przestarzałe w v1.3.0)**

⚠️ **Ta metoda jest przestarzała od v1.3.0.** Zamiast tego używaj `isStreaming(): bool` dla lepszej przejrzystości.

Ta metoda określa typ przetwarzania wiadomości dla twojego narzędzia. Zwraca wartość enum `ProcessMessageType`. Dostępne typy to:

- `ProcessMessageType::HTTP`: Dla narzędzi współpracujących przez standardowe żądanie/odpowiedź HTTP. Najczęściej dla nowych narzędzi.
- `ProcessMessageType::SSE`: Dla narzędzi specjalnie zaprojektowanych do pracy z Server-Sent Events.

Dla większości narzędzi, szczególnie tych zaprojektowanych dla głównego providera `streamable_http`, zwrócisz `ProcessMessageType::HTTP`.

**`isStreaming(): bool` (Nowe w v1.3.0)**

To jest nowa, bardziej intuicyjna metoda do kontrolowania wzorców komunikacji:

- `return false`: Używaj standardowego żądania/odpowiedzi HTTP (zalecane dla większości narzędzi)
- `return true`: Używaj Server-Sent Events dla streamingu w czasie rzeczywistym

Większość narzędzi powinna zwracać `false`, chyba że specjalnie potrzebujesz możliwości streamingu w czasie rzeczywistym, takich jak:
- Aktualizacje postępu w czasie rzeczywistym dla długotrwałych operacji
- Kanały danych na żywo lub narzędzia monitorowania
- Narzędzia interaktywne wymagające komunikacji dwukierunkowej

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
                'description' => 'The unique identifier for the user.',
            ],
            'includeDetails' => [
                'type' => 'boolean',
                'description' => 'Whether to include extended details in the response.',
                'default' => false, // Możesz określić wartości domyślne
            ],
        ],
        'required' => ['userId'], // Określa które właściwości są obowiązkowe
    ];
}
```

W swojej metodzie `execute` możesz następnie walidować przychodzące argumenty. Przykład `HelloWorldTool` używa `Illuminate\Support\Facades\Validator` do tego:

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
// Kontynuuj z zwalidowanymi $arguments['userId'] i $arguments['includeDetails']
```

**`annotations(): array`**

Ta metoda dostarcza metadane o zachowaniu i charakterystykach twojego narzędzia, podążając za oficjalną [specyfikacją MCP Tool Annotations](https://modelcontextprotocol.io/docs/concepts/tools#tool-annotations). Adnotacje pomagają klientom MCP kategoryzować narzędzia, podejmować świadome decyzje o zatwierdzaniu narzędzi i dostarczać odpowiednie interfejsy użytkownika.

**Standardowe adnotacje MCP:**

Model Context Protocol definiuje kilka standardowych adnotacji, które klienci rozumieją:

- **`title`** (string): Czytelny dla człowieka tytuł narzędzia, wyświetlany w interfejsach klientów
- **`readOnlyHint`** (boolean): Wskazuje, czy narzędzie tylko odczytuje dane bez modyfikowania środowiska (domyślnie: false)
- **`destructiveHint`** (boolean): Sugeruje, czy narzędzie może wykonywać destrukcyjne operacje jak usuwanie danych (domyślnie: true)
- **`idempotentHint`** (boolean): Wskazuje, czy powtórne wywołania z tymi samymi argumentami nie mają dodatkowego efektu (domyślnie: false)
- **`openWorldHint`** (boolean): Sygnalizuje, czy narzędzie współpracuje z zewnętrznymi encjami poza lokalnym środowiskiem (domyślnie: true)

**Ważne:** To są wskazówki, nie gwarancje. Pomagają klientom zapewnić lepsze doświadczenia użytkownika, ale nie powinny być używane do decyzji krytycznych dla bezpieczeństwa.

**Przykład ze standardowymi adnotacjami MCP:**

```php
public function annotations(): array
{
    return [
        'title' => 'User Profile Fetcher',
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
        'title' => 'Database Query Tool',
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
        'title' => 'Blog Post Deletion Tool',
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
        'title' => 'Weather API',
        'readOnlyHint' => true,
        'destructiveHint' => false,
        'idempotentHint' => true,
        'openWorldHint' => true,       // Dostępuje zewnętrzne API pogody
    ];
}
```

**Własne adnotacje** mogą być również dodane dla specyficznych potrzeb twojej aplikacji:

```php
public function annotations(): array
{
    return [
        // Standardowe adnotacje MCP
        'title' => 'Custom Tool',
        'readOnlyHint' => true,

        // Własne adnotacje dla twojej aplikacji
        'category' => 'data-analysis',
        'version' => '2.1.0',
        'author' => 'Data Team',
        'requires_permission' => 'analytics.read',
    ];
}
```

### Praca z zasobami

Zasoby ujawniają dane z twojego serwera, które mogą być odczytywane przez klientów MCP. Są **kontrolowane przez aplikację**, co oznacza, że klient decyduje kiedy i jak ich używać. Twórz konkretne zasoby lub szablony URI w `app/MCP/Resources` i `app/MCP/ResourceTemplates` używając pomocników Artisan:

```bash
php artisan make:mcp-resource SystemLogResource
php artisan make:mcp-resource-template UserLogTemplate
```

Zarejestruj wygenerowane klasy w `config/mcp-server.php` pod tablicami `resources` i `resource_templates`. Każda klasa zasobu rozszerza bazową klasę `Resource` i implementuje metodę `read()`, która zwraca zawartość `text` lub `blob`. Szablony rozszerzają `ResourceTemplate` i opisują dynamiczne wzorce URI, których klienci mogą używać. Zasób jest identyfikowany przez URI takie jak `file:///logs/app.log` i może opcjonalnie definiować metadane jak `mimeType` lub `size`.

**Szablony zasobów z dynamicznym listowaniem**: Szablony mogą opcjonalnie implementować metodę `list()` do dostarczania konkretnych instancji zasobów, które pasują do wzorca szablonu. To pozwala klientom dynamicznie odkrywać dostępne zasoby. Metoda `list()` umożliwia instancjom ResourceTemplate generowanie listy konkretnych zasobów, które mogą być odczytywane przez metodę `read()` szablonu.

Wylistuj dostępne zasoby używając punktu końcowego `resources/list` i odczytaj ich zawartość za pomocą `resources/read`. Punkt końcowy `resources/list` zwraca tablicę konkretnych zasobów, włączając zarówno statyczne zasoby, jak i dynamicznie generowane zasoby z szablonów, które implementują metodę `list()`:

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

**Dynamiczne odczytywanie zasobów**: Szablony zasobów obsługują wzorce szablonów URI (RFC 6570), które pozwalają klientom konstruować dynamiczne identyfikatory zasobów. Gdy klient żąda URI zasobu, który pasuje do wzorca szablonu, metoda `read()` szablonu jest wywoływana z wyodrębnionymi parametrami do wygenerowania zawartości zasobu.

Przykładowy przepływ pracy:
1. Szablon definiuje wzorzec: `"database://users/{userId}/profile"`
2. Klient żąda: `"database://users/123/profile"`
3. Szablon wyodrębnia `{userId: "123"}` i wywołuje metodę `read()`
4. Szablon zwraca dane profilu użytkownika dla ID użytkownika 123

Możesz także wylistować szablony oddzielnie używając punktu końcowego `resources/templates/list`:

```bash
# Wylistuj tylko szablony zasobów
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/templates/list"}'
```

Gdy uruchamiasz swój serwer Laravel MCP zdalnie, transport HTTP działa ze standardowymi żądaniami JSON-RPC. Oto prosty przykład używający `curl` do listowania i odczytywania zasobów:

```bash
# Wylistuj zasoby
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"resources/list"}'

# Odczytaj konkretny zasób
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":2,"method":"resources/read","params":{"uri":"file:///logs/app.log"}}'
```

Serwer odpowiada wiadomościami JSON przesyłanymi strumieniowo przez połączenie HTTP, więc `curl --no-buffer` może być używane, jeśli chcesz widzieć przyrostowe wyjście.

### Praca z promptami

Prompty dostarczają wielokrotnego użytku fragmenty tekstu z obsługą argumentów, które twoje narzędzia lub użytkownicy mogą żądać. Twórz klasy promptów w `app/MCP/Prompts` używając:

```bash
php artisan make:mcp-prompt WelcomePrompt
```

Zarejestruj je w `config/mcp-server.php` pod `prompts`. Każda klasa promptu rozszerza bazową klasę `Prompt` i definiuje:
- `name`: Unikalny identyfikator (np. "welcome-user")
- `description`: Opcjonalny czytelny dla człowieka opis
- `arguments`: Tablica definicji argumentów z polami name, description i required
- `text`: Szablon promptu z placeholderami jak `{username}`

Wylistuj prompty przez punkt końcowy `prompts/list` i pobierz je używając `prompts/get` z argumentami:

```bash
# Pobierz prompt powitalny z argumentami
curl -X POST https://your-server.com/mcp \
  -H "Content-Type: application/json" \
  -d '{"jsonrpc":"2.0","id":1,"method":"prompts/get","params":{"name":"welcome-user","arguments":{"username":"Alice","role":"admin"}}}'
```

### Prompty MCP

Tworząc prompty, które odwołują się do twoich narzędzi lub zasobów, skonsultuj się z [oficjalnymi wytycznymi promptów](https://modelcontextprotocol.io/docs/concepts/prompts). Prompty to szablony wielokrotnego użytku, które mogą przyjmować argumenty, zawierać kontekst zasobów, a nawet opisywać wieloetapowe przepływy pracy.

**Struktura promptu**

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

Klienci odkrywają prompty przez `prompts/list` i żądają konkretnych przez `prompts/get`:

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

**Przykład klasy promptu**

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

Prompty mogą osadzać zasoby i zwracać sekwencje wiadomości do prowadzenia LLM. Zobacz oficjalną dokumentację dla zaawansowanych przykładów i najlepszych praktyk.

### Praca z powiadomieniami

Powiadomienia to wiadomości typu fire-and-forget od klientów MCP, które zawsze zwracają HTTP 202 Accepted bez treści odpowiedzi. Są idealne do logowania, śledzenia postępu, obsługi zdarzeń i wyzwalania procesów w tle bez blokowania klienta.

#### Tworzenie obsługi powiadomień

**Podstawowe użycie komendy:**

```bash
php artisan make:mcp-notification ProgressHandler --method=notifications/progress
```

**Zaawansowane funkcje komendy:**

```bash
# Tryb interaktywny - pyta o metodę jeśli nie została określona
php artisan make:mcp-notification MyHandler

# Automatyczne obsługiwanie prefiksu metody
php artisan make:mcp-notification StatusHandler --method=status  # staje się notifications/status

# Normalizacja nazwy klasy 
php artisan make:mcp-notification "user activity"  # staje się UserActivityHandler
```

Komenda zapewnia:
- **Interaktywne pytanie o metodę** gdy `--method` nie jest określony
- **Automatyczny przewodnik rejestracji** z gotowym do skopiowania kodem
- **Wbudowane przykłady testów** z komendami curl 
- **Kompleksowe instrukcje użycia** i powszechne przypadki użycia

#### Architektura obsługi powiadomień

Każda obsługa powiadomień musi implementować abstrakcyjną klasę `NotificationHandler`:

```php
abstract class NotificationHandler
{
    // Wymagane: Typ wiadomości (zwykle ProcessMessageType::HTTP)
    protected const MESSAGE_TYPE = ProcessMessageType::HTTP;
    
    // Wymagane: Metoda powiadomienia do obsługi  
    protected const HANDLE_METHOD = 'notifications/your_method';
    
    // Wymagane: Wykonanie logiki powiadomienia
    abstract public function execute(?array $params = null): void;
}
```

**Kluczowe komponenty architektoniczne:**

- **`MESSAGE_TYPE`**: Zwykle `ProcessMessageType::HTTP` dla standardowych powiadomień
- **`HANDLE_METHOD`**: Metoda JSON-RPC, którą przetwarza ta obsługa (musi zaczynać się od `notifications/`)
- **`execute()`**: Zawiera twoją logikę powiadomień - zwraca void (nie wysyła odpowiedzi)
- **Walidacja konstruktora**: Automatycznie waliduje, czy wymagane stałe są zdefiniowane

#### Wbudowane obsługi powiadomień

Pakiet zawiera cztery prebudowane obsługi dla powszechnych scenariuszy MCP:

**1. InitializedHandler (`notifications/initialized`)**
- **Cel**: Przetwarza potwierdzenia inicjalizacji klienta po udanym handshake
- **Parametry**: Informacje o kliencie i możliwości
- **Użycie**: Śledzenie sesji, logowanie klienta, zdarzenia inicjalizacji

**2. ProgressHandler (`notifications/progress`)**
- **Cel**: Obsługuje aktualizacje postępu dla długotrwałych operacji
- **Parametry**: 
  - `progressToken` (string): Unikalny identyfikator operacji
  - `progress` (number): Bieżąca wartość postępu
  - `total` (number, opcjonalnie): Całkowita wartość postępu do obliczenia procentu
- **Użycie**: Śledzenie postępu w czasie rzeczywistym, monitorowanie uploadów, ukończenie zadań

**3. CancelledHandler (`notifications/cancelled`)**
- **Cel**: Przetwarza powiadomienia o anulowaniu żądań
- **Parametry**:
  - `requestId` (string): ID żądania do anulowania
  - `reason` (string, opcjonalnie): Powód anulowania
- **Użycie**: Zakończenie zadań w tle, czyszczenie zasobów, przerywanie operacji

**4. MessageHandler (`notifications/message`)**
- **Cel**: Obsługuje ogólne wiadomości logowania i komunikacji
- **Parametry**:
  - `level` (string): Poziom loga (info, warning, error, debug)
  - `message` (string): Treść wiadomości
  - `logger` (string, opcjonalnie): Nazwa loggera
- **Użycie**: Logowanie po stronie klienta, debugowanie, ogólna komunikacja

#### Przykłady obsługi dla powszechnych scenariuszy

```php
// Śledzenie postępu uploadu plików
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
            
            // Transmituj aktualizację w czasie rzeczywistym
            broadcast(new UploadProgressUpdated($token, $progress, $total));
        }
    }
}

// Aktywność użytkownika i logowanie audytu
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
        
        // Wyzwól alerty bezpieczeństwa dla wrażliwych działań
        if (in_array($params['action'] ?? '', ['delete', 'export', 'admin_access'])) {
            SecurityAlert::dispatch($params);
        }
    }
}

// Wyzwalanie zadań w tle
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

#### Rejestrowanie obsługi powiadomień

**W twoim dostawcy usług:**

```php
// W AppServiceProvider lub dedykowanym dostawcy usług MCP
public function boot()
{
    $server = app(MCPServer::class);
    
    // Zarejestruj wbudowane obsługi (opcjonalnie - są rejestrowane domyślnie)
    $server->registerNotificationHandler(new InitializedHandler());
    $server->registerNotificationHandler(new ProgressHandler());
    $server->registerNotificationHandler(new CancelledHandler());
    $server->registerNotificationHandler(new MessageHandler());
    
    // Zarejestruj niestandardowe obsługi
    $server->registerNotificationHandler(new UploadProgressHandler());
    $server->registerNotificationHandler(new UserActivityHandler());
    $server->registerNotificationHandler(new TaskTriggerHandler());
}
```

#### Testowanie powiadomień

**Używając curl do testowania obsługi powiadomień:**

```bash
# Testuj powiadomienie o postępie
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
# Oczekiwane: HTTP 202 z pustą treścią

# Testuj powiadomienie o aktywności użytkownika  
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
# Oczekiwane: HTTP 202 z pustą treścią

# Testuj powiadomienie o anulowaniu
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
# Oczekiwane: HTTP 202 z pustą treścią
```

**Kluczowe uwagi dotyczące testowania:**
- Powiadomienia zwracają **HTTP 202** (nigdy 200)
- Treść odpowiedzi jest **zawsze pusta**
- Nie jest wysyłana wiadomość odpowiedzi JSON-RPC
- Sprawdź logi serwera aby zweryfikować przetwarzanie powiadomień

#### Obsługa błędów i walidacja

**Powszechne wzorce walidacji:**

```php
public function execute(?array $params = null): void
{
    // Waliduj wymagane parametry
    if (!isset($params['userId'])) {
        Log::error('UserActivityHandler: Missing required userId parameter', $params);
        return; // Nie rzucaj wyjątkiem - powiadomienia powinny być odporne na błędy
    }
    
    // Waliduj typy parametrów
    if (!is_numeric($params['userId'])) {
        Log::warning('UserActivityHandler: userId must be numeric', $params);
        return;
    }
    
    // Bezpieczna ekstrakcja parametrów z domyślnymi wartościami
    $userId = (int) $params['userId'];
    $action = $params['action'] ?? 'unknown';
    $metadata = $params['metadata'] ?? [];
    
    // Przetwarzaj powiadomienie...
}
```

**Najlepsze praktyki obsługi błędów:**
- **Loguj błędy** zamiast rzucać wyjątkami
- **Używaj programowania defensywnego** ze sprawdzaniem null i domyślnymi wartościami
- **Graceful failure** - nie psuj przepływu pracy klienta
- **Waliduj wejścia** ale kontynuuj przetwarzanie gdy to możliwe
- **Monitoruj powiadomienia** poprzez logowanie i metryki

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
- Wykonywanie narzędzia z twoim dostarczonym wejściem
- Wyświetlanie sformatowanych wyników lub szczegółowych informacji o błędach
- Obsługę złożonych typów wejściowych włączając obiekty i tablice

### Wizualizacja narzędzi MCP z Inspektorem

Możesz także używać Model Context Protocol Inspector do wizualizacji i testowania swoich narzędzi MCP:

```bash
# Uruchom MCP Inspector bez instalacji
npx @modelcontextprotocol/inspector node build/index.js
```

To zazwyczaj otworzy interfejs webowy na `localhost:6274`. Aby przetestować swój serwer MCP:

1. **Ostrzeżenie**: `php artisan serve` NIE MOŻE być używane z tym pakietem, ponieważ nie może obsługiwać wielu połączeń PHP jednocześnie. Ponieważ MCP SSE wymaga przetwarzania wielu połączeń równocześnie, musisz użyć jednej z tych alternatyw:

   - **Laravel Octane** (Najłatwiejsza opcja):

     ```bash
     # Zainstaluj i skonfiguruj Laravel Octane z FrankenPHP (zalecane)
     composer require laravel/octane
     php artisan octane:install --server=frankenphp

     # Uruchom serwer Octane
     php artisan octane:start
     ```

     > **Ważne**: Instalując Laravel Octane, upewnij się, że używasz FrankenPHP jako serwera. Pakiet może nie działać prawidłowo z RoadRunner z powodu problemów kompatybilności z połączeniami SSE. Jeśli możesz pomóc naprawić ten problem kompatybilności z RoadRunner, prześlij Pull Request - twój wkład byłby bardzo doceniony!

     Szczegóły znajdziesz w [dokumentacji Laravel Octane](https://laravel.com/docs/12.x/octane)

   - **Opcje produkcyjne**:
     - Nginx + PHP-FPM
     - Apache + PHP-FPM
     - Własna konfiguracja Docker

   * Każdy serwer webowy, który prawidłowo obsługuje streaming SSE (wymagane tylko dla legacy providera SSE)

2. W interfejsie Inspektora wprowadź URL punktu końcowego MCP twojego serwera Laravel (np. `http://localhost:8000/mcp`). Jeśli używasz legacy providera SSE, użyj zamiast tego URL SSE (`http://localhost:8000/mcp/sse`).
3. Połącz się i eksploruj dostępne narzędzia wizualnie

Punkt końcowy MCP podąża za wzorcem: `http://[twój-serwer-laravel]/[default_path]` gdzie `default_path` jest zdefiniowany w twoim pliku `config/mcp-server.php`.

## Zaawansowane funkcje

### Architektura Pub/Sub z adapterami SSE (legacy provider)

Pakiet implementuje wzorzec komunikatów publish/subscribe (pub/sub) przez swój system adapterów:

1. **Publisher (Serwer)**: Gdy klienci wysyłają żądania do punktu końcowego `/message`, serwer przetwarza te żądania i publikuje odpowiedzi przez skonfigurowany adapter.

2. **Message Broker (Adapter)**: Adapter (np. Redis) utrzymuje kolejki wiadomości dla każdego klienta, identyfikowane przez unikalne ID klientów. To zapewnia niezawodną warstwę komunikacji asynchronicznej.

3. **Subscriber (połączenie SSE)**: Długotrwałe połączenia SSE subskrybują wiadomości dla swoich odpowiednich klientów i dostarczają je w czasie rzeczywistym. To dotyczy tylko używania legacy providera SSE.

Ta architektura umożliwia:

- Skalowalną komunikację w czasie rzeczywistym
- Niezawodne dostarczanie wiadomości nawet podczas tymczasowych rozłączeń
- Wydajną obsługę wielu równoczesnych połączeń klientów
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

## Tłumaczenie README.md

Aby przetłumaczyć ten README na inne języki używając Claude API (Przetwarzanie równoległe):

```bash
pip install -r scripts/requirements.txt
export ANTHROPIC_API_KEY='your-api-key'
python scripts/translate_readme.py
```

Możesz także tłumaczyć konkretne języki:

```bash
python scripts/translate_readme.py es ko
```

## Funkcje przestarzałe dla v2.0.0

Następujące funkcje są przestarzałe i zostaną usunięte w v2.0.0. Proszę odpowiednio zaktualizować swój kod:

### Zmiany ToolInterface

**Przestarzałe od v1.3.0:**
- Metoda `messageType(): ProcessMessageType`
- **Zamiennik:** Używaj zamiast tego `isStreaming(): bool`
- **Przewodnik migracji:** Zwracaj `false` dla narzędzi HTTP, `true` dla narzędzi streamingowych
- **Automatyczna migracja:** Uruchom `php artisan mcp:migrate-tools` aby zaktualizować swoje narzędzia

**Przykład migracji:**

```php
// Stare podejście (przestarzałe)
public function messageType(): ProcessMessageType
{
    return ProcessMessageType::HTTP;
}

// Nowe podejście (v1.3.0+)
public function isStreaming(): bool
{
    return false; // Używaj false dla HTTP, true dla streamingu
}
```

### Usunięte funkcje

**Usunięte w v1.3.0:**
- Case enum `ProcessMessageType::PROTOCOL` (skonsolidowany do `ProcessMessageType::HTTP`)

**Planowane dla v2.0.0:**
- Całkowite usunięcie metody `messageType()` z `ToolInterface`
- Wszystkie narzędzia będą wymagane do implementacji tylko metody `isStreaming()`
- Uproszczona konfiguracja narzędzi i zmniejszona złożoność

## Licencja

Ten projekt jest dystrybuowany na licencji MIT.