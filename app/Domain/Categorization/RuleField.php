<?php

namespace App\Domain\Categorization;

enum RuleField: string
{
    case Merchant = 'merchant';
    case Description = 'description';
    case Any = 'any';

    public function label(): string
    {
        return match ($this) {
            self::Merchant => 'Sprzedawca',
            self::Description => 'Opis',
            self::Any => 'Sprzedawca lub opis',
        };
    }
}
