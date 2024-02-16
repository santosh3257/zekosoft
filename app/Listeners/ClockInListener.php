<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\ClockInEvent;
use App\Notifications\ClockIn;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class ClockInListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ClockInEvent  $event
     * @return void
     */
    public function handle(ClockInEvent $event)
    {
        $company = $event->attendance->company;
        Notification::send(User::allAdmins($company->id)->first(), new ClockIn($event->attendance));

    }

}
