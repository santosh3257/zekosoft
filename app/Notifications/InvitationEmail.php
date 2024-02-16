<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class InvitationEmail extends BaseNotification
{

    /**
     * @var UserInvitation
     */
    private $invite;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(UserInvitation $invite)
    {
        $this->invite = $invite;
        $this->company = $invite->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    // phpcs:ignore
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return MailMessage
     */
    // phpcs:ignore
    public function toMail($notifiable): MailMessage
    {
        $build = parent::build();
        if(!empty($this->invite->redirect_url)){
           $url = $this->invite->redirect_url.'/'.$this->invite->invitation_code; 
        }
        else{
            $url = route('invitation', Lang::get($this->invite->invitation_code));
            $url = getDomainSpecificUrl($url, $this->company); 
        }

        $content = $this->invite->user->name . ' ' . __('email.invitation.subject') . config('app.name') . '.'  . '<br>' . $this->invite->message;

        return $build
            ->subject($this->invite->user->name . ' ' . __('email.invitation.subject') . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __(Lang::get('email.invitation.action'))
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    //phpcs:ignore
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

}
