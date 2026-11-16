<?php

namespace App\Domain\Categorization;

enum RuleOperator: string
{
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case Equals = 'equals';

    public function label(): string
    {
        return match ($this) {
            self::Contains => 'zawiera',
            self::StartsWith => 'zaczyna się od',
            self::Equals => 'jest równe',
        };
    }

    public function test(string $text, string $pattern): bool
    {
        return match ($this) {
            self::Contains => str_contains($text, $pattern),
            self::StartsWith => str_starts_with($text, $pattern),
            self::Equals => $text === $pattern,
        };
    }
}
