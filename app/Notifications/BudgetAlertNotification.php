<?php

namespace App\Notifications;

use App\Domain\Shared\Money;
use App\Models\BudgetAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly BudgetAlert $alert) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $spent = Money::ofCents($this->alert->spent)->decimal(',');
        $limit = Money::ofCents($this->alert->limit)->decimal(',');

        return (new MailMessage)
            ->subject("{$this->alert->level->label()}: {$this->alert->targetName()}")
            ->greeting('Cześć '.$notifiable->name.',')
            ->line("W miesiącu {$this->alert->month} wydałeś {$spent} zł z limitu {$limit} zł ({$this->alert->targetName()}).")
            ->action('Zobacz budżet', url('/'));
    }
}
