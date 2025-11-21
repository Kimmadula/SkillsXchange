<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endDateTime = $this->trade->end_date . ' ' . $this->trade->available_to;
        $offeringSkill = optional($this->trade->offeringSkill)->name ?? 'N/A';
        $lookingSkill = optional($this->trade->lookingSkill)->name ?? 'N/A';
        
        return (new MailMessage)
            ->subject("Session Expiring Soon: {$this->hoursBefore} hours remaining - SkillsXchange")
            ->greeting("Hello {$notifiable->firstname}!")
            ->line("Your skill exchange session is expiring soon!")
            ->line("**Session Details:**")
            ->line("• **Expires in:** {$this->hoursBefore} hours")
            ->line("• **End Date & Time:** {$endDateTime}")
            ->line("• **Offering Skill:** {$offeringSkill}")
            ->line("• **Looking For:** {$lookingSkill}")
            ->action('View Session', url("/trades/{$this->trade->id}/session"))
            ->line("Make sure to complete your session before it expires!")
            ->line('If you have any questions, feel free to contact us.');
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


