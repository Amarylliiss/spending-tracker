# Spendings Tracker

Mała aplikacja do kontrolowania domowego budżetu. Zapisujesz wydatki, a reguły same przypisują im kategorie. Na każdą kategorię i na cały miesiąc możesz ustawić limit, a po jego przekroczeniu dostajesz alert. Dane wyeksportujesz do CSV.

## Funkcje

- **Dodawanie wydatków**: kwota, data, sprzedawca, opis i opcjonalnie kategoria wybrana ręcznie.
- **Automatyczne kategorie z reguł**: warunki typu *„sprzedawca zawiera «orlen» → Transport”*, z priorytetami. Reguły można też zastosować wstecz do już zapisanych wydatków. Kategoria wybrana ręcznie nigdy nie jest nadpisywana.
- **Limity miesięczne**: osobno dla każdej kategorii i dla całego budżetu.
- **Alerty**: przy 80% i po przekroczeniu 100% limitu. Każdy próg zgłaszany jest raz w miesiącu, w aplikacji i e-mailem.
- **Eksport CSV**: wybrany miesiąc lub kategoria. Separator `;` i BOM, żeby polski Excel otworzył plik bez konwersji.

## Technologie

Laravel 13 (PHP 8.4), Sanctum, MySQL, Redis (kolejka, cache, sesje), Vue 3 + Pinia + Vue Router, Tailwind 4, Pest, Docker.

## Struktura

```
app/
├── Domain/          czysta logika, bez frameworka
│   ├── Shared/          Money (kwoty w groszach), Month
│   ├── Categorization/  Rule, RuleMatcher, pola i operatory reguł
│   └── Budget/          BudgetEvaluator, progi alertów
├── Application/     przypadki użycia
│   ├── Expenses/        SaveExpense, ApplyRules, Categorizer
│   ├── Budget/          MonthlySummary, CheckBudgets, SpendingTotals
│   ├── Export/          ExpenseCsvExport
│   └── Onboarding/      startowe kategorie i reguły dla nowego konta
├── Events/          SpendingChanged, BudgetThresholdReached
├── Listeners/       CheckMonthlyBudgets, SendBudgetAlert (w kolejce)
├── Notifications/   BudgetAlertNotification (e-mail)
├── Policies/        dostęp tylko do własnych danych
├── Http/            kontrolery API, walidacja, zasoby JSON
└── Models/          modele Eloquent
```

Każda zmiana wydatku albo limitu wysyła zdarzenie `SpendingChanged`. Listener w kolejce przelicza budżet danego miesiąca i zapisuje nowe alerty. Następnie zgłasza `BudgetThresholdReached`, a drugi listener wysyła e-mail. Gdy jeden wydatek przekroczy od razu 80% i 100%, przychodzi tylko jedna wiadomość.

## Uruchomienie (Docker)

```bash
cp .env.example .env
docker compose --profile assets run --rm assets   # zbudowanie frontendu
docker compose up -d
docker compose exec app php artisan db:seed       # konto demo
```

Aplikacja działa pod adresem http://localhost:8080. Konto demo: `demo@example.com` / `password`.

## Uruchomienie bez Dockera

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
# w .env: DB_CONNECTION=sqlite, QUEUE_CONNECTION=sync, CACHE_STORE=file, SESSION_DRIVER=file
php artisan migrate --seed
php artisan serve
```

## Testy

```bash
php artisan test
```

Testy jednostkowe obejmują domenę: kwoty, miesiące, dopasowanie reguł i progi budżetu. Testy funkcjonalne sprawdzają API: kategoryzację, ponowne stosowanie reguł, alerty i e-maile, eksport CSV oraz izolację danych między użytkownikami.

## API

| Metoda | Ścieżka | Opis |
| --- | --- | --- |
| POST | `/api/auth/register`, `/api/auth/login` | konto i token |
| GET/POST | `/api/expenses` | lista (filtry `month`, `category_id`, `q`) i dodawanie |
| PUT/DELETE | `/api/expenses/{id}` | edycja i usuwanie |
| GET | `/api/expenses/export?month=RRRR-MM` | eksport CSV |
| GET/POST/PUT/DELETE | `/api/categories` | kategorie z limitem miesięcznym |
| GET/POST/PUT/DELETE | `/api/rules` | reguły kategoryzacji |
| POST | `/api/rules/apply` | zastosowanie reguł do zapisanych wydatków |
| GET | `/api/budget?month=RRRR-MM` | podsumowanie miesiąca |
| PUT | `/api/budget/limit` | limit na cały miesiąc |
| GET | `/api/alerts` | alerty budżetu |
