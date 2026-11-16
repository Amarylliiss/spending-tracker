<?php

namespace App\Domain\Shared;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class Month
{
    private function __construct(public CarbonImmutable $start) {}

    public static function fromString(string $value): self
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            throw new InvalidArgumentException('Miesiąc musi mieć format RRRR-MM.');
        }

        return new self(CarbonImmutable::createFromFormat('!Y-m', $value));
    }

    public static function of(DateTimeInterface $date): self
    {
        return new self(CarbonImmutable::instance($date)->startOfMonth()->startOfDay());
    }

    public static function current(): self
    {
        return self::of(CarbonImmutable::now());
    }

    public function key(): string
    {
        return $this->start->format('Y-m');
    }

    public function firstDay(): string
    {
        return $this->start->toDateString();
    }

    public function lastDay(): string
    {
        return $this->start->endOfMonth()->toDateString();
    }

    public function previous(): self
    {
        return new self($this->start->subMonthNoOverflow());
    }
}
