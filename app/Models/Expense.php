<?php

namespace App\Models;

use App\Domain\Categorization\CategorizedBy;
use App\Domain\Shared\Month;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_id', 'rule_id', 'amount', 'spent_on', 'merchant', 'description', 'categorized_by'])]
class Expense extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'spent_on' => 'immutable_date',
            'categorized_by' => CategorizedBy::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<CategoryRule, $this> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(CategoryRule::class);
    }

    /**
     * @param  Builder<Expense>  $query
     */
    public function scopeInMonth(Builder $query, Month $month): void
    {
        $query->whereBetween('spent_on', [$month->firstDay(), $month->lastDay()]);
    }
}
