<?php

namespace App\Events;

use App\Models\BudgetAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BudgetThresholdReached
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly BudgetAlert $alert) {}
}
