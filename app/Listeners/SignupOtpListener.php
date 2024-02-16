<?php

namespace App\Listeners;

use App\Events\SignupOtpEvent;
use App\Notifications\SignupOtpCode;

class SignupOtpListener
{

    /**
     * Handle the event.
     *
     * @param  \App\Events\SignupOtpEvent  $event
     * @return void
     */
    public function handle(SignupOtpEvent $event)
    {
        $event->user->notify(new SignupOtpCode());
    }
}
