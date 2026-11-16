<?php

namespace App\Application\Expenses;

use App\Domain\Categorization\CategorizedBy;
use App\Domain\Shared\Month;
use App\Events\SpendingChanged;
use App\Models\Expense;
use App\Models\User;

class ApplyRules
{
    public function __construct(private readonly Categorizer $categorizer) {}

    /**
     * Re-runs the rules on every expense that was not categorized by hand.
     * Returns the number of expenses whose category changed.
     */
    public function handle(User $user): int
    {
        $this->categorizer->forget($user);

        $changed = 0;
        $months = [];

        $user->expenses()
            ->where('categorized_by', '!=', CategorizedBy::Manual->value)
            ->lazyById()
            ->each(function (Expense $expense) use ($user, &$changed, &$months): void {
                $rule = $this->categorizer->match($user, $expense->merchant, $expense->description);

                $expense->fill([
                    'category_id' => $rule?->categoryId,
                    'rule_id' => $rule?->id,
                    'categorized_by' => $rule ? CategorizedBy::Rule : CategorizedBy::None,
                ]);

                if ($expense->isDirty('category_id')) {
                    $changed++;
                    $months[] = Month::of($expense->spent_on)->key();
                }

                $expense->save();
            });

        if ($months !== []) {
            SpendingChanged::dispatch($user->id, array_values(array_unique($months)));
        }

        return $changed;
    }
}
