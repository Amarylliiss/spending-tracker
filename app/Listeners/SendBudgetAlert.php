<?php

namespace App\Listeners;

use App\Events\BudgetThresholdReached;
use App\Notifications\BudgetAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBudgetAlert implements ShouldQueue
{
    public function handle(BudgetThresholdReached $event): void
    {
        $event->alert->user->notify(new BudgetAlertNotification($event->alert));
    }
}
