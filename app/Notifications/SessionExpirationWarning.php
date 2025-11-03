<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SessionExpirationWarning extends Notification implements ShouldQueue
{
    use Queueable;

    protected $trade;
    protected $hoursBefore;

    public function __construct($trade, int $hoursBefore)
    {
        $this->trade = $trade;
        $this->hoursBefore = $hoursBefore;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_expiration_warning',
            'trade_id' => $this->trade->id,
            'hours_before' => $this->hoursBefore,
            'message' => "Your session will expire in {$this->hoursBefore} hours.",
            'end_date' => $this->trade->end_date,
            'available_to' => $this->trade->available_to,
            'skill_pair' => [
                'offering' => optional($this->trade->offeringSkill)->name,
                'looking' => optional($this->trade->lookingSkill)->name,
            ],
        ];
    }
}


