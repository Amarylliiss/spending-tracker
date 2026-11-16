<?php

use App\Application\Onboarding\CreateStarterCategories;
use App\Events\BudgetThresholdReached;
use App\Models\BudgetAlert;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use App\Notifications\BudgetAlertNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

function signIn(): User
{
    $user = User::factory()->create();
    app(CreateStarterCategories::class)->handle($user);
    Sanctum::actingAs($user);

    return $user;
}

function category(User $user, string $name): Category
{
    return $user->categories()->where('name', $name)->firstOrFail();
}

it('seeds a demo account that can sign in', function () {
    $this->seed();

    $this->postJson('/api/auth/login', ['email' => 'demo@example.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('user.name', 'Anna Kowalska');

    expect(BudgetAlert::query()->count())->toBeGreaterThan(0);
});

it('registers a user with starter categories and rules', function () {
    $this->postJson('/api/auth/register', [
        'name' => 'Ola',
        'email' => 'ola@example.com',
        'password' => 'sekretne-haslo',
        'password_confirmation' => 'sekretne-haslo',
    ])->assertCreated()->assertJsonPath('user.email', 'ola@example.com');

    $user = User::query()->where('email', 'ola@example.com')->firstOrFail();

    expect($user->categories()->count())->toBe(7)
        ->and($user->rules()->count())->toBeGreaterThan(10);
});

it('categorizes a new expense with the matching rule', function () {
    $user = signIn();

    $this->postJson('/api/expenses', [
        'amount' => '42,50',
        'spent_on' => today()->toDateString(),
        'merchant' => 'Biedronka nr 312',
        'description' => 'Zakupy',
    ])
        ->assertCreated()
        ->assertJsonPath('amount', 4250)
        ->assertJsonPath('category.name', 'Jedzenie')
        ->assertJsonPath('categorized_by', 'rule');
});

it('keeps a category chosen by hand', function () {
    $user = signIn();

    $this->postJson('/api/expenses', [
        'amount' => '15',
        'spent_on' => today()->toDateString(),
        'merchant' => 'Biedronka',
        'description' => 'Prezent',
        'category_id' => category($user, 'Inne')->id,
    ])->assertCreated()->assertJsonPath('categorized_by', 'manual');

    $user->rules()->create([
        'category_id' => category($user, 'Zdrowie')->id,
        'field' => 'description',
        'operator' => 'contains',
        'pattern' => 'prezent',
        'priority' => 1,
    ]);

    $this->postJson('/api/rules/apply')->assertOk()->assertJsonPath('changed', 0);

    expect(Expense::query()->first()->category->name)->toBe('Inne');
});

it('applies a new rule to expenses that had no category', function () {
    $user = signIn();

    $this->postJson('/api/expenses', [
        'amount' => '120',
        'spent_on' => today()->toDateString(),
        'merchant' => 'Siłownia Zdrofit',
        'description' => 'Karnet',
    ])->assertJsonPath('category', null);

    $this->postJson('/api/rules', [
        'category_id' => category($user, 'Zdrowie')->id,
        'field' => 'merchant',
        'operator' => 'starts_with',
        'pattern' => 'siłownia',
    ])->assertCreated();

    $this->postJson('/api/rules/apply')->assertJsonPath('changed', 1);

    expect(Expense::query()->first()->category->name)->toBe('Zdrowie');
});

it('raises one alert and one e-mail when a limit is exceeded', function () {
    Notification::fake();
    $user = signIn();

    $this->patchJson('/api/categories/'.category($user, 'Rozrywka')->id, [
        'name' => 'Rozrywka',
        'color' => '#d1495b',
        'monthly_limit' => '100',
    ])->assertOk()->assertJsonPath('monthly_limit', 10000);

    $this->postJson('/api/expenses', [
        'amount' => '130',
        'spent_on' => today()->toDateString(),
        'merchant' => 'Kino',
        'description' => 'Bilety i popcorn',
    ])->assertCreated();

    expect(BudgetAlert::query()->pluck('level')->map->value->sort()->values()->all())->toBe([80, 100]);
    Notification::assertSentToTimes($user, BudgetAlertNotification::class, 1);

    $this->postJson('/api/expenses', [
        'amount' => '20',
        'spent_on' => today()->toDateString(),
        'merchant' => 'Steam',
        'description' => 'Gra',
    ]);

    expect(BudgetAlert::query()->count())->toBe(2);
    Notification::assertSentToTimes($user, BudgetAlertNotification::class, 1);

    $this->getJson('/api/alerts')
        ->assertJsonPath('unread', 2)
        ->assertJsonPath('data.0.target', 'Rozrywka');
});

it('watches the overall monthly limit too', function () {
    Event::fake([BudgetThresholdReached::class]);
    $user = signIn();

    $this->putJson('/api/budget/limit', ['monthly_limit' => '200'])->assertJsonPath('monthly_limit', 20000);

    $this->postJson('/api/expenses', [
        'amount' => '170',
        'spent_on' => today()->toDateString(),
        'description' => 'Różne',
    ]);

    $this->getJson('/api/budget')
        ->assertJsonPath('total.spent', 17000)
        ->assertJsonPath('total.level', 'warning')
        ->assertJsonPath('uncategorized', 17000);

    Event::assertDispatched(
        BudgetThresholdReached::class,
        fn ($event) => $event->alert->category_id === null,
    );
});

it('exports the month to csv', function () {
    $user = signIn();

    foreach (['=SUMA(A1)' => '10,5', 'Lidl' => '99.99'] as $merchant => $amount) {
        $this->postJson('/api/expenses', [
            'amount' => $amount,
            'spent_on' => today()->toDateString(),
            'merchant' => $merchant,
            'description' => 'Zakupy',
        ]);
    }

    $csv = $this->get('/api/expenses/export?month='.today()->format('Y-m'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)->toContain('Data;Sprzedawca;Opis;Kategoria;Kwota')
        ->toContain(';Lidl;Zakupy;Jedzenie;99,99;reguła')
        ->toContain("'=SUMA(A1)");
});

it('does not let users touch each other\'s data', function () {
    $owner = signIn();
    $expense = $owner->expenses()->create([
        'amount' => 1000,
        'spent_on' => today(),
        'description' => 'Prywatne',
    ]);

    signIn();

    $this->deleteJson("/api/expenses/{$expense->id}")->assertForbidden();
    $this->patchJson('/api/categories/'.category($owner, 'Inne')->id, ['name' => 'X', 'color' => '#000000'])->assertForbidden();
    $this->getJson('/api/expenses')->assertJsonCount(0, 'data');
});

it('rejects expenses from the future and zero amounts', function () {
    signIn();

    $this->postJson('/api/expenses', [
        'amount' => '0,00',
        'spent_on' => today()->addDay()->toDateString(),
        'description' => 'Test',
    ])->assertJsonValidationErrors(['amount', 'spent_on']);
});
