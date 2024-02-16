<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use App\Notifications\BaseNotification;
use Modules\RestAPI\Entities\UsersOtp; 

class ForgotPasswordOtpNotification extends BaseNotification
{

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $signupOtp = UsersOtp::where('user_id',$notifiable->user_auth_id)->first();
        $twoFaCode = '<p style="color:#1d82f5"><strong>' . $signupOtp->otp . '</strong></p>';

        $content = __('email.forgotOtp.line1') . '<br>' . new HtmlString($twoFaCode) . '<br>' . __('email.twoFactor.line2') . '<br>' . __('email.twoFactor.line3');

        return parent::build()
            ->markdown('mail.email', [
                'content' => $content,
                'notifiableName' => ''
            ]);
    }
}
