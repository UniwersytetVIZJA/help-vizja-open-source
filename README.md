<p align="center">
    <img
        src="public/images/logo/unijne-logotypy/pl.png"
        alt="Logo Funduszy Europejskich"
        height="100"
    >
</p>

<p align="center">
    <img
        src="public/images/logo/uv/logo_pl_PL_poziom.png"
        alt="Logo Uniwersytetu Vizja"
        height="50"
    >
</p>

---

# Help VIZJA

**Help VIZJA** to aplikacja internetowa wspierająca obsługę studentów, pracowników oraz procesów administracyjnych Biura ds. Osób z Niepełnosprawnościami Uniwersytetu VIZJA.
Aplikacja umożliwia między innymi obsługę wniosków studenckich, zapisów na konsultacje, wizyt w Biurze ds. Osób z Niepełnosprawnościami, ogłoszeń, ankiet, inwentaryzacji sprzętu oraz
komunikacji z wykorzystaniem usług Microsoft.

---

## Główne funkcjonalności

- obsługa elektronicznych wniosków studenckich,
- zarządzanie statusami wniosków,
- dodawanie komentarzy pracowników,
- generowanie dokumentów DOCX i PDF,
- obsługa załączników,
- zapisy na konsultacje,
- zapisy na wizyty w Biurze ds. Osób z Niepełnosprawnościami,
- powiadomienia e-mail,
- przypomnienia o wizytach,
- integracja z Microsoft Teams,
- integracja z Microsoft Graph API,
- logowanie przez konto Microsoft / Office 365,
- zarządzanie użytkownikami,
- system ogłoszeń,
- inwentaryzacja sprzętu,
- słowniki systemowe,
- wielojęzyczność: język polski i angielski,
- elementy zgodne z wymaganiami dostępności cyfrowej WCAG.

---

## Technologie

Projekt wykorzystuje:

- PHP 8.4,
- Symfony 7,
- Doctrine ORM,
- MySQL,
- Twig,
- Tailwind CSS,
- Flowbite,
- JavaScript,
- Webpack Encore,
- Node.js,
- npm / yarn,
- Docker,
- Microsoft Graph API,
- Microsoft Teams,
- OAuth 2.0 / Microsoft Entra ID,
- Messenger,
- CQRS,
- Domain-Driven Design,
- KnpPaginator,
- PhpWord,
- FPDI / PDF,
- Symfony Mailer.

---

## Wymagania

Do uruchomienia projektu lokalnie wymagane są:

- PHP 8.4 lub nowszy,
- Composer,
- MySQL 8 lub kompatybilna baza danych,
- Node.js,
- npm lub yarn,
- Symfony CLI,
- Docker i Docker Compose — opcjonalnie.

---

## Instalacja projektu

### 1. Klonowanie repozytorium

```bash
git clone https://github.com/UniwersytetVIZJA/help-vizja-open-source.git
```

```bash
cd help-vizja-open-source
```

---

### 2. Konfiguracja środowiska

Skopiuj plik konfiguracyjny:

```bash
cp .env .env.local
```

Następnie uzupełnij wymagane zmienne środowiskowe, między innymi:

```env
DATABASE_URL="mysql://docker:docker@mysql:3100/docker?serverVersion=8.0&charset=utf8mb4"

APP_ENV=dev
APP_SECRET=change_me

MAILER_DSN=null://null
MAIL_FROM_ADDRESS=noreply@example.com
```

Jeżeli projekt ma korzystać z integracji Microsoft, należy również uzupełnić zmienne związane z Microsoft Entra ID, OAuth 2.0 oraz Microsoft Graph API.

Jeżeli aplikacja korzysta z integracji z systemem dziekanatowym, należy również skonfigurować odpowiednie zmienne środowiskowe opisane w dalszej części dokumentacji.

---

### 3. Instalacja zależności PHP

```bash
composer install
```

### 4. Uruchomienie aplikacji

Przy użyciu Dockera:

```bash
make build
```

```bash
make start
```

Aplikacja będzie dostępna domyślnie pod adresem:

```text
http://localhost
```

---

### 5. Utworzenie bazy danych (jeżeli nie została utworzona automatycznie)

```bash
php bin/console doctrine:database:create
```

---

### 6. Uruchomienie migracji

```bash
php bin/console doctrine:migrations:diff
```

```bash
php bin/console doctrine:migrations:migrate
```

---

### 7. Załadowanie danych testowych / fixtures

Jeżeli projekt posiada przygotowane fixtures:

```bash
php bin/console doctrine:fixtures:load
```

Podczas wykonywania tej komendy baza danych może zostać wyczyszczona i wypełniona przykładowymi danymi.

### Testowi użytkownicy

Po załadowaniu danych testowych (`doctrine:fixtures:load`) w aplikacji dostępne są przykładowe konta użytkowników.

### Administrator

| Pole  | Wartość                    |
|-------|----------------------------|
| Login | `email@example.pl`         |
| Hasło | Logowanie przez Office 365 |
| Rola  | `ROLE_ADMIN`               |

### Student

| Pole  | Wartość                    |
|-------|----------------------------|
| Login | `test@example.pl`          |
| Hasło | Logowanie przez Office 365 |
| Rola  | `ROLE_STUDENT`             |

### Gość

| Pole  | Wartość            |
|-------|--------------------|
| Login | `test2@example.pl` |
| Hasło | `test`             |
| Rola  | `ROLE_GOSC`        |

> **Uwaga**
>
> Powyższe konta są tworzone wyłącznie przez dane testowe (Fixtures) znajdujące się w katalogu:
>
> ```text
> src/Database/Fixture
> ```
>
> Konta te przeznaczone są wyłącznie do uruchamiania projektu lokalnie, testów oraz celów demonstracyjnych. Przed wdrożeniem aplikacji na środowisko produkcyjne należy usunąć dane testowe oraz
> utworzyć własne konta użytkowników.

### 8. Instalacja zależności frontendowych

Podstawowo:

```bash
npm install
```

Jeżeli npm nie działa poprawnie, można użyć yarn:

```bash
yarn install
```

---

### 9. Budowanie zasobów frontendowych

W trybie deweloperskim:

```bash
npm run dev
```

lub:

```bash
yarn dev
```

W trybie produkcyjnym:

```bash
npm run build
```

lub:

```bash
yarn build
```

---

## Uruchomienie z Dockerem

Jeżeli projekt jest uruchamiany z wykorzystaniem Dockera:

```bash
docker compose up -d
```

Następnie wewnątrz kontenera PHP należy wykonać:

```bash
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
npm install
npm run build
```

Nazwy kontenerów oraz dokładny sposób wejścia do kontenera mogą zależeć od lokalnej konfiguracji `docker-compose.yaml`.

---

## Struktura katalogów

Najważniejsze katalogi projektu:

```text
assets/          zasoby frontendowe
bin/             pliki wykonywalne Symfony
config/          konfiguracja aplikacji
docs/            dokumentacja projektu
migrations/      migracje bazy danych
public/          katalog publiczny aplikacji
src/             kod źródłowy PHP
templates/       szablony Twig
translations/    pliki tłumaczeń
var/             pliki tymczasowe i cache
```

---

## Integracje

Projekt może korzystać z następujących integracji:

- Microsoft Graph API,
- Microsoft Teams,
- logowanie Microsoft / Office 365,
- poczta e-mail,
- generator dokumentów DOCX,
- generator dokumentów PDF.

---

## Integracja z systemem dziekanatowym

Aplikacja została zaprojektowana w sposób umożliwiający integrację z zewnętrznym systemem dziekanatowym uczelni.

Domyślnie projekt wykorzystuje **Verbis - Wirtualny Dziekanat (VDO)**, który służy do pobierania danych studentów oraz synchronizacji informacji niezbędnych do działania aplikacji. Verbis jest jednym
z
systemów obsługi toku studiów wykorzystywanych przez polskie uczelnie wyższe. ([verbis.pl](https://www.verbis.pl/))

Aby aplikacja działała poprawnie, należy skonfigurować połączenie z własnym systemem dziekanatowym.

W pliku `.env` należy uzupełnić następujące zmienne:

```env
###> System dziekanatowy ###
VERBIS_VDO_API_URL=
VERBIS_VDO_LOGIN=
VERBIS_VDO_PASSWORD=
###< System dziekanatowy ###
```

### Opis zmiennych

| Zmienna               | Opis                                                        |
|-----------------------|-------------------------------------------------------------|
| `VERBIS_VDO_API_URL`  | Adres endpointu API lub usługi systemu dziekanatowego.      |
| `VERBIS_VDO_LOGIN`    | Login użytkownika technicznego posiadającego dostęp do API. |
| `VERBIS_VDO_PASSWORD` | Hasło użytkownika technicznego.                             |

> **Uwaga**
>
> Projekt zawiera przykładową implementację integracji z systemem Verbis VDO. W przypadku korzystania z innego systemu dziekanatowego należy zaimplementować własny serwis komunikujący się z
> odpowiednim API oraz dostosować konfigurację aplikacji.

Ze względów bezpieczeństwa dane dostępowe do systemu dziekanatowego **nie są częścią repozytorium** i powinny być przechowywane wyłącznie w zmiennych środowiskowych lub bezpiecznym magazynie sekretów.

---

## Bezpieczeństwo

Przed uruchomieniem projektu w środowisku produkcyjnym należy:

- ustawić bezpieczną wartość `APP_SECRET`,
- skonfigurować prawidłowe dane dostępowe do bazy danych,
- skonfigurować produkcyjny `MAILER_DSN`,
- ustawić poprawne dane integracji Microsoft,
- wyłączyć tryb deweloperski,
- nie publikować pliku `.env`,
- nie publikować katalogów `var/`, `vendor/`, `node_modules/`, `public/uploads/`.

---

## Dostępność cyfrowa

Projekt został przygotowany z uwzględnieniem standardu dostępności cyfrowej WCAG 2.1 na poziomie A i AA. W aplikacji zastosowano między innymi:

- semantyczny HTML,
- obsługę klawiatury,
- widoczne style fokusu,
- odpowiedni kontrast czcionki i elementów interfejsu,
- responsywny interfejs,
- natywnie skalowalne treści dostosowujące się do indywidualnych ustawień użytkowników,
- semantycznie powiązane etykiety formularzy,
- odpowiednią komunikację błędów formularzy,
- odpowiednie nazwy i role elementów interfejsu,
- skiplinki – możliwość pominięcia bloku z nawigacją,
- hierarchiczne nagłówki,
- mapę strony,
- wielojęzyczne treści,
- alternatywny tryb ciemny aplikacji.

**Ważna informacja:**<br>
Należy pamiętać, że każda modyfikacja kodu, stylów CSS lub struktury szablonów może wpłynąć na poziom zgodności z WCAG. Podmioty wtórnie wykorzystujące produkt lub adaptujące go do własnych celów są
zobowiązane do przeprowadzenia niezależnej oceny dostępności cyfrowej oraz własnej deklaracji dostępności po wprowadzeniu jakichkolwiek zmian.

---

## Licencja

Projekt jest udostępniany na licencji
**Creative Commons Attribution 4.0 International (CC BY 4.0)**.

https://creativecommons.org/licenses/by/4.0/legalcode.en

Oznacza to, że można kopiować, rozpowszechniać, zmieniać i wykorzystywać projekt, również komercyjnie, pod warunkiem odpowiedniego oznaczenia autorstwa.

---

## Informacja o finansowaniu

Aplikacja została przygotowana w ramach realizacji projektu **"Vizja Home - Uczelnia dla wszystkich"** w ramach programu Fundusze Europejskie dla Rozwoju Społecznego 2021-2027 współfinansowanego ze
środków Europejskiego Funduszu Społecznego Plus.
Numer umowy o dofinansowanie: **FERS.03.01-IP.08-0200/24**

---

## Autorstwo

Projekt przygotowany dla:

**Uniwersytet VIZJA**

Repozytorium stanowi przykład systemu wspierającego procesy administracyjne uczelni oraz działania Biura ds. Osób z Niepełnosprawnościami.
