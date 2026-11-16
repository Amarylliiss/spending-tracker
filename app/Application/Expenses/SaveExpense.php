<?php

namespace App\Application\Expenses;

use App\Domain\Categorization\CategorizedBy;
use App\Domain\Shared\Month;
use App\Events\SpendingChanged;
use App\Models\Expense;
use App\Models\User;

class SaveExpense
{
    public function __construct(private readonly Categorizer $categorizer) {}

    public function handle(User $user, ExpenseData $data, ?Expense $expense = null): Expense
    {
        $expense ??= new Expense(['user_id' => $user->id]);
        $months = $expense->exists ? [Month::of($expense->spent_on)->key()] : [];

        $expense->fill([
            'amount' => $data->amount->cents,
            'spent_on' => $data->spentOn,
            'description' => $data->description,
            'merchant' => $data->merchant,
        ]);

        if ($data->categoryId !== null) {
            $expense->fill([
                'category_id' => $data->categoryId,
                'rule_id' => null,
                'categorized_by' => CategorizedBy::Manual,
            ]);
        } else {
            $rule = $this->categorizer->match($user, $data->merchant, $data->description);

            $expense->fill([
                'category_id' => $rule?->categoryId,
                'rule_id' => $rule?->id,
                'categorized_by' => $rule ? CategorizedBy::Rule : CategorizedBy::None,
            ]);
        }

        $expense->save();

        $months[] = Month::of($expense->spent_on)->key();
        SpendingChanged::dispatch($user->id, array_values(array_unique($months)));

        return $expense->load('category');
    }
}
