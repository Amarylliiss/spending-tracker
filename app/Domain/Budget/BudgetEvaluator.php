<?php

namespace App\Domain\Budget;

use App\Domain\Shared\Money;

final class BudgetEvaluator
{
    /**
     * @return list<AlertLevel>
     */
    public function reachedLevels(Money $spent, Money $limit): array
    {
        if ($limit->isZero()) {
            return [];
        }

        return array_values(array_filter(
            AlertLevel::cases(),
            fn (AlertLevel $level) => $spent->cents * 100 >= $limit->cents * $level->value,
        ));
    }

    public function highestLevel(Money $spent, Money $limit): ?AlertLevel
    {
        $levels = $this->reachedLevels($spent, $limit);

        return $levels === [] ? null : end($levels);
    }
}
