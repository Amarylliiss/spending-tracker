<?php

namespace App\Models;

use App\Domain\Budget\AlertLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_id', 'month', 'level', 'spent', 'limit', 'read_at'])]
class BudgetAlert extends Model
{
    protected function casts(): array
    {
        return [
            'level' => AlertLevel::class,
            'spent' => 'integer',
            'limit' => 'integer',
            'read_at' => 'datetime',
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

    public function targetName(): string
    {
        return $this->category?->name ?? 'Cały budżet';
    }
}
