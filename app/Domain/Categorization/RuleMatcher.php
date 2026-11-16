<?php

namespace App\Domain\Categorization;

final class RuleMatcher
{
    /**
     * Rules are checked by priority (lower first), then by age, so the outcome
     * does not depend on the order they were loaded in.
     *
     * @param  iterable<Rule>  $rules
     */
    public function firstMatch(iterable $rules, ?string $merchant, string $description): ?Rule
    {
        $ordered = [...$rules];

        usort($ordered, fn (Rule $a, Rule $b) => [$a->priority, $a->id] <=> [$b->priority, $b->id]);

        foreach ($ordered as $rule) {
            if ($rule->matches($merchant, $description)) {
                return $rule;
            }
        }

        return null;
    }
}
