<?php

// app/Notifications/UpcomingEventNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Event;
use Carbon\Carbon;

class UpcomingEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $startDate = Carbon::parse($this->event->start_date);
        $daysUntilEvent = now()->diffInDays($startDate);

        return (new MailMessage)
            ->subject('Upcoming Event: ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('This is a reminder about an upcoming event you\'re registered for.')
            ->line('Event: ' . $this->event->title)
            ->line('Date: ' . $startDate->format('F j, Y g:i A'))
            ->line('Location: ' . $this->event->location)
            ->line('The event starts in ' . $daysUntilEvent . ' days.')
            ->action('View Event Details', url('http://localhost:5173/events/' . $this->event->id))
            ->line('We look forward to seeing you there!');
    }
}
