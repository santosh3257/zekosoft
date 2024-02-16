<?php

namespace App\Notifications;

use App\Models\Project;

class NewProjectStatus extends BaseNotification
{


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    private $projectStatus;

    /**
     * @var \Illuminate\Contracts\Foundation\Application|\Illuminate\Session\SessionManager|\Illuminate\Session\Store|mixed
     */

    public function __construct(Project $projectStatus)
    {

        $this->projectStatus = $projectStatus;
        $this->company = $this->projectStatus->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = ['database'];

        if ($notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $build = parent::build();

        $url = route('projects.show', $this->projectStatus->id);
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('email.newProjectStatus.text') . ' - ' . ucfirst($this->projectStatus->status) . '. ' . __('email.newProjectStatus.loginNow');

        return $build
            ->subject(__('email.newProjectStatus.subject') . ' - ' . config('app.name') . '.')
            ->markdown('mail.projectStatus.created', [
                'url' => $url,
                'content' => $content,
                'name' => $notifiable->name,
                'notifiableName' => $notifiable->name,
                'themeColor' => $this->company->header_color
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
        return $this->projectStatus->toArray();
    }

}
