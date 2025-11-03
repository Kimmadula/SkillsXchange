<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreSessionReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $trade;
    protected $minutesBefore;

    public function __construct($trade, int $minutesBefore)
    {
        $this->trade = $trade;
        $this->minutesBefore = $minutesBefore;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'pre_session_reminder',
            'trade_id' => $this->trade->id,
            'minutes_before' => $this->minutesBefore,
            'message' => "Your session starts in {$this->minutesBefore} minutes.",
            'start_date' => $this->trade->start_date,
            'available_from' => $this->trade->available_from,
            'skill_pair' => [
                'offering' => optional($this->trade->offeringSkill)->name,
                'looking' => optional($this->trade->lookingSkill)->name,
            ],
        ];
    }
}


