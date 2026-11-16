<?php

namespace Database\Seeders;

use App\Application\Budget\CheckBudgets;
use App\Application\Expenses\ExpenseData;
use App\Application\Expenses\SaveExpense;
use App\Application\Onboarding\CreateStarterCategories;
use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;

class DatabaseSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const SAMPLES = [
        ['Biedronka', 'Zakupy spożywcze', '186.40'],
        ['Orlen', 'Tankowanie', '242.10'],
        ['Netflix', 'Abonament', '60.00'],
        ['Żabka', 'Kawa i bułka', '18.90'],
        ['Apteka Gemini', 'Leki na przeziębienie', '54.30'],
        ['Bolt', 'Przejazd na lotnisko', '71.00'],
        ['Lidl', 'Zakupy na weekend', '231.75'],
        ['Pyszne.pl', 'Pizza ze znajomymi', '98.50'],
        ['Tauron', 'Rachunek za prąd', '212.00'],
        ['Spotify', 'Premium Duo', '29.99'],
        ['Kino Helios', 'Bilety', '64.00'],
        ['Rossmann', 'Kosmetyki', '87.20'],
        ['Allegro', 'Ładowarka do telefonu', '49.99'],
        ['Carrefour', 'Większe zakupy', '312.60'],
        ['Jakdojade', 'Bilet miesięczny', '110.00'],
    ];

    public function run(CreateStarterCategories $starter, SaveExpense $save, CheckBudgets $check): void
    {
        $user = User::factory()->create([
            'name' => 'Anna Kowalska',
            'email' => 'demo@example.com',
            'monthly_limit' => Money::parse('3200')->cents,
        ]);

        $starter->handle($user);

        $limits = ['Jedzenie' => '900', 'Transport' => '400', 'Rozrywka' => '150', 'Restauracje' => '250'];

        foreach ($limits as $name => $limit) {
            $user->categories()->where('name', $name)->update(['monthly_limit' => Money::parse($limit)->cents]);
        }

        $today = CarbonImmutable::today();

        Event::fakeFor(function () use ($user, $save, $today): void {
            foreach ([1, 0] as $monthsAgo) {
                $start = $today->startOfMonth()->subMonthsNoOverflow($monthsAgo);
                $lastDay = $monthsAgo === 0 ? $today->day : $start->daysInMonth;

                foreach (self::SAMPLES as $index => [$merchant, $description, $amount]) {
                    $day = 1 + ($index * 2 + $monthsAgo * 3) % $lastDay;

                    $save->handle($user, ExpenseData::fromArray([
                        'merchant' => $merchant,
                        'description' => $description,
                        'amount' => $monthsAgo ? Money::ofCents(intdiv(Money::parse($amount)->cents * 9, 10))->decimal() : $amount,
                        'spent_on' => $start->setDay($day)->toDateString(),
                    ]));
                }
            }
        });

        $check->handle($user, Month::current());
    }
}
