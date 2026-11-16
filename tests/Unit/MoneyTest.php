<?php

use App\Domain\Shared\Money;
use App\Domain\Shared\Month;

it('parses amounts written with a comma or a dot', function (string $input, int $cents) {
    expect(Money::parse($input)->cents)->toBe($cents);
})->with([
    ['24,99', 2499],
    ['24.9', 2490],
    ['1 200', 120000],
    ['0.05', 5],
]);

it('rejects amounts that are not money', function (string $input) {
    Money::parse($input);
})->throws(InvalidArgumentException::class)->with(['-5', 'abc', '1.999', '']);

it('formats cents back to a decimal string', function () {
    expect(Money::ofCents(120005)->decimal(','))->toBe('1200,05');
});

it('knows the first and last day of a month', function () {
    $month = Month::fromString('2024-02');

    expect($month->firstDay())->toBe('2024-02-01')
        ->and($month->lastDay())->toBe('2024-02-29')
        ->and($month->previous()->key())->toBe('2024-01');
});
