<?php

namespace App\Models;

use App\Domain\Categorization\Rule;
use App\Domain\Categorization\RuleField;
use App\Domain\Categorization\RuleOperator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'category_id', 'field', 'operator', 'pattern', 'priority'])]
class CategoryRule extends Model
{
    protected function casts(): array
    {
        return [
            'field' => RuleField::class,
            'operator' => RuleOperator::class,
            'priority' => 'integer',
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

    public function toRule(): Rule
    {
        return new Rule(
            id: $this->id,
            categoryId: $this->category_id,
            field: $this->field,
            operator: $this->operator,
            pattern: $this->pattern,
            priority: $this->priority,
        );
    }
}
