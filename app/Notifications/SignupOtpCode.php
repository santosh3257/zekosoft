<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use App\Notifications\BaseNotification;
use Modules\RestAPI\Entities\UsersOtp; 
class SignupOtpCode extends BaseNotification
{

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $this->company = $notifiable->company;
        $signupOtp = UsersOtp::where('user_id',$notifiable->user_auth_id)->first();
        $twoFaCode = '<p style="color:#1d82f5"><strong>' . $signupOtp->otp . '</strong></p>';

        $content = __('email.signOtp.line1') . '<br>' . new HtmlString($twoFaCode) . '<br>' . __('email.twoFactor.line2') . '<br>' . __('email.twoFactor.line3');

        return parent::build()
            ->markdown('mail.email', [
                'content' => $content,
                'notifiableName' => ''
            ]);
    }

}
