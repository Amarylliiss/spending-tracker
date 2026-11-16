<?php

use App\Domain\Budget\AlertLevel;
use App\Domain\Budget\BudgetEvaluator;
use App\Domain\Shared\Money;

it('reports the thresholds reached by spending', function (string $spent, array $levels) {
    expect((new BudgetEvaluator)->reachedLevels(Money::parse($spent), Money::parse('500')))->toBe($levels);
})->with([
    'well below' => ['100', []],
    'just under warning' => ['399.99', []],
    'warning' => ['400', [AlertLevel::Warning]],
    'exactly at limit' => ['500', [AlertLevel::Warning, AlertLevel::Exceeded]],
    'over' => ['720', [AlertLevel::Warning, AlertLevel::Exceeded]],
]);

it('ignores a zero limit', function () {
    expect((new BudgetEvaluator)->highestLevel(Money::parse('50'), Money::zero()))->toBeNull();
});
