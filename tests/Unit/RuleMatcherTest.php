<?php

use App\Domain\Categorization\Rule;
use App\Domain\Categorization\RuleField;
use App\Domain\Categorization\RuleMatcher;
use App\Domain\Categorization\RuleOperator;

it('matches regardless of letter case and extra spaces', function () {
    $rule = new Rule(1, 10, RuleField::Merchant, RuleOperator::Contains, 'żabka');

    expect($rule->matches('  ŻABKA   Nano ', 'kawa'))->toBeTrue()
        ->and($rule->matches('Biedronka', 'żabka'))->toBeFalse();
});

it('checks both merchant and description when asked to', function () {
    $rule = new Rule(1, 10, RuleField::Any, RuleOperator::StartsWith, 'czynsz');

    expect($rule->matches(null, 'Czynsz za listopad'))->toBeTrue()
        ->and($rule->matches('Spółdzielnia', 'Opłata: czynsz'))->toBeFalse();
});

it('picks the rule with the lowest priority number first', function () {
    $general = new Rule(1, 10, RuleField::Any, RuleOperator::Contains, 'orlen', priority: 100);
    $specific = new Rule(2, 20, RuleField::Description, RuleOperator::Contains, 'hot-dog', priority: 10);

    $match = (new RuleMatcher)->firstMatch([$general, $specific], 'Orlen', 'Hot-dog i kawa');

    expect($match?->categoryId)->toBe(20);
});

it('returns nothing when no rule matches', function () {
    $rule = new Rule(1, 10, RuleField::Merchant, RuleOperator::Equals, 'lidl');

    expect((new RuleMatcher)->firstMatch([$rule], 'Lidl Polska', 'zakupy'))->toBeNull();
});
