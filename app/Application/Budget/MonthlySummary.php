<?php

namespace App\Application\Budget;

use App\Domain\Budget\BudgetEvaluator;
use App\Domain\Shared\Money;
use App\Domain\Shared\Month;
use App\Models\Category;
use App\Models\User;

class MonthlySummary
{
    public function __construct(
        private readonly BudgetEvaluator $evaluator,
        private readonly SpendingTotals $totals,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, Month $month): array
    {
        $spent = $this->totals->byCategory($user, $month);
        $previous = $this->totals->byCategory($user, $month->previous());

        $categories = $user->categories()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                ...$this->line(Money::ofCents($spent[$category->id] ?? 0), $category->monthly_limit),
            ])
            ->sortByDesc('spent')
            ->values()
            ->all();

        return [
            'month' => $month->key(),
            'total' => [
                ...$this->line(Money::ofCents(array_sum($spent)), $user->monthly_limit),
                'previous' => array_sum($previous),
                'count' => $user->expenses()->inMonth($month)->count(),
            ],
            'categories' => $categories,
            'uncategorized' => $spent[0] ?? 0,
        ];
    }

    /**
     * @return array{spent: int, limit: ?int, percent: ?float, level: ?string}
     */
    private function line(Money $spent, ?int $limit): array
    {
        if (! $limit) {
            return ['spent' => $spent->cents, 'limit' => null, 'percent' => null, 'level' => null];
        }

        $limitMoney = Money::ofCents($limit);
        $level = $this->evaluator->highestLevel($spent, $limitMoney);

        return [
            'spent' => $spent->cents,
            'limit' => $limit,
            'percent' => $spent->percentOf($limitMoney),
            'level' => $level === null ? null : strtolower($level->name),
        ];
    }
}
