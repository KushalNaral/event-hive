<?php
namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Event;

class EventRegistrationConfirmation extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Registration Confirmed: ' . $this->event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your registration for the following event has been confirmed:')
            ->line('Event: ' . $this->event->title)
            ->line('Date: ' . Carbon::parse($this->event->start_date)->format('F j, Y g:i A'))
            ->line('Location: ' . $this->event->location)
            ->line('Expected Participants: ' . $this->event->expected_participants)
            ->action('View Event Details', url('http://localhost:5173/events/' . $this->event->id))
            ->line('Thank you for registering! We\'ll send you a reminder as the event date approaches.');
    }
}
