<?php
namespace App\Console\Commands;

use App\Models\UserInteractions;
use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\User;
use App\Notifications\UpcomingEventNotification;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';
    protected $description = 'Send reminders for upcoming events';

    public function handle()
    {
        $upcomingEvents = Event::where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDays(7))
            ->get();

        $registeredUsersIds = UserInteractions::where('interaction_type', 'attend')
            ->distinct('user_id')
            ->pluck('user_id')
            ->toArray();

        $registeredUsers = User::where('id', $registeredUsersIds)->get();

        foreach ($upcomingEvents as $event) {
            foreach ($registeredUsers as $user) {
                $user->notify(new UpcomingEventNotification($event));
            }
        }

        $this->info('Event reminders sent successfully!');
    }
}

