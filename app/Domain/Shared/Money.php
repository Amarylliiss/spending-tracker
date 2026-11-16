<?php

namespace App\Domain\Shared;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(public int $cents) {}

    public static function ofCents(int $cents): self
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Kwota nie może być ujemna.');
        }

        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function parse(string|int|float $value): self
    {
        $normalized = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim((string) $value));

        if (! preg_match('/^\d{1,9}(\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Nieprawidłowa kwota.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized), 2, '');

        return new self((int) $whole * 100 + (int) str_pad($fraction, 2, '0'));
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function percentOf(self $limit): float
    {
        if ($limit->isZero()) {
            return 0.0;
        }

        return round($this->cents / $limit->cents * 100, 1);
    }

    public function decimal(string $separator = '.'): string
    {
        return intdiv($this->cents, 100).$separator.str_pad((string) ($this->cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
