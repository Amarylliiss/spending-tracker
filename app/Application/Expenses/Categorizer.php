<?php

namespace App\Application\Expenses;

use App\Domain\Categorization\Rule;
use App\Domain\Categorization\RuleMatcher;
use App\Models\CategoryRule;
use App\Models\User;

class Categorizer
{
    /** @var array<int, list<Rule>> */
    private array $rules = [];

    public function __construct(private readonly RuleMatcher $matcher) {}

    public function match(User $user, ?string $merchant, string $description): ?Rule
    {
        $this->rules[$user->id] ??= $user->rules()
            ->get()
            ->map(fn (CategoryRule $rule) => $rule->toRule())
            ->all();

        return $this->matcher->firstMatch($this->rules[$user->id], $merchant, $description);
    }

    public function forget(User $user): void
    {
        unset($this->rules[$user->id]);
    }
}
