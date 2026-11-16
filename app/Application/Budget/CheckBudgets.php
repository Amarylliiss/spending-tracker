<?php

namespace App\Application\Budget;

use App\Domain\Budget\BudgetEvaluator;
use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Models\BudgetAlert;
use App\Models\Category;
use App\Models\User;

class CheckBudgets
{
    public function __construct(
        private readonly BudgetEvaluator $evaluator,
        private readonly SpendingTotals $totals,
    ) {}

    /**
     * Creates an alert for every threshold reached in the month that has not
     * been reported yet, and returns only the new ones.
     *
     * @return list<BudgetAlert>
     */
    public function handle(User $user, Month $month): array
    {
        $spent = $this->totals->byCategory($user, $month);
        $alerts = [];

        $user->categories()
            ->whereNotNull('monthly_limit')
            ->get()
            ->each(function (Category $category) use ($user, $month, $spent, &$alerts): void {
                array_push($alerts, ...$this->raise(
                    $user,
                    $month,
                    $category,
                    Money::ofCents($spent[$category->id] ?? 0),
                    Money::ofCents($category->monthly_limit),
                ));
            });

        if ($user->monthly_limit) {
            array_push($alerts, ...$this->raise(
                $user,
                $month,
                null,
                Money::ofCents(array_sum($spent)),
                Money::ofCents($user->monthly_limit),
            ));
        }

        return $alerts;
    }

    /**
     * @return list<BudgetAlert>
     */
    private function raise(User $user, Month $month, ?Category $category, Money $spent, Money $limit): array
    {
        $created = [];

        foreach ($this->evaluator->reachedLevels($spent, $limit) as $level) {
            $alert = BudgetAlert::query()->firstOrCreate([
                'user_id' => $user->id,
                'category_id' => $category?->id,
                'month' => $month->key(),
                'level' => $level->value,
            ], [
                'spent' => $spent->cents,
                'limit' => $limit->cents,
            ]);

            if ($alert->wasRecentlyCreated) {
                $created[] = $alert->setRelation('category', $category);
            }
        }

        return $created;
    }
}
