<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SpendingChanged
{
    use Dispatchable;

    /**
     * @param  list<string>  $months  months in YYYY-MM format
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $months,
    ) {}
}
