<?php

namespace App\Application\Budget;

use App\Domain\Shared\Month;
use App\Models\User;

class SpendingTotals
{
    /**
     * Spent amount in cents keyed by category id; uncategorized expenses use key 0.
     *
     * @return array<int, int>
     */
    public function byCategory(User $user, Month $month): array
    {
        return $user->expenses()
            ->inMonth($month)
            ->selectRaw('category_id, sum(amount) as total')
            ->groupBy('category_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->category_id => (int) $row->total])
            ->all();
    }
}
