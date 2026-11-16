<?php

namespace App\Domain\Budget;

enum AlertLevel: int
{
    case Warning = 80;
    case Exceeded = 100;

    public function label(): string
    {
        return match ($this) {
            self::Warning => 'Wykorzystano 80% limitu',
            self::Exceeded => 'Limit przekroczony',
        };
    }
}
