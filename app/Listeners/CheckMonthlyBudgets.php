<?php

namespace App\Listeners;

use App\Application\Budget\CheckBudgets;
use App\Domain\Shared\Month;
use App\Events\BudgetThresholdReached;
use App\Events\SpendingChanged;
use App\Models\BudgetAlert;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckMonthlyBudgets implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly CheckBudgets $checkBudgets) {}

    public function handle(SpendingChanged $event): void
    {
        $user = User::query()->find($event->userId);

        if ($user === null) {
            return;
        }

        foreach ($event->months as $month) {
            $alerts = $this->checkBudgets->handle($user, Month::fromString($month));

            // Crossing 80% and 100% with one expense should produce a single message.
            collect($alerts)
                ->groupBy(fn (BudgetAlert $alert) => $alert->category_id ?? 0)
                ->map(fn ($group) => $group->sortByDesc(fn (BudgetAlert $alert) => $alert->level->value)->first())
                ->each(fn (BudgetAlert $alert) => BudgetThresholdReached::dispatch($alert));
        }
    }
}
