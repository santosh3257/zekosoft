<?php

namespace App\Listeners;

use App\Events\ForgotPasswordOtpEvent;
use App\Notifications\ForgotPasswordOtpNotification;

class ForgotPasswordOtpListener
{
 
    /**
     * Handle the event.
     */
    public function handle(ForgotPasswordOtpEvent $event)
    {
        $event->user->notify(new ForgotPasswordOtpNotification());
    }
}
