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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $startDateTime = $this->trade->start_date . ' ' . $this->trade->available_from;
        $offeringSkill = optional($this->trade->offeringSkill)->name ?? 'N/A';
        $lookingSkill = optional($this->trade->lookingSkill)->name ?? 'N/A';
        
        return (new MailMessage)
            ->subject("Session Reminder: Starts in {$this->minutesBefore} minutes - SkillsXchange")
            ->greeting("Hello {$notifiable->firstname}!")
            ->line("Your skill exchange session is starting soon!")
            ->line("**Session Details:**")
            ->line("• **Starts in:** {$this->minutesBefore} minutes")
            ->line("• **Start Date & Time:** {$startDateTime}")
            ->line("• **Offering Skill:** {$offeringSkill}")
            ->line("• **Looking For:** {$lookingSkill}")
            ->action('View Session', url("/trades/{$this->trade->id}/session"))
            ->line("Don't forget to join your session on time!")
            ->line('If you have any questions, feel free to contact us.');
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


