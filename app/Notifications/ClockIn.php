<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use App\Models\EmailNotificationSetting;
use Illuminate\Notifications\Messages\SlackMessage;

class ClockIn extends BaseNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

     private $attendance;
     private $emailSetting;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->company = $this->attendance->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'clock-in-notification')->first();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
    {
        $via = [];

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            array_push($via, 'slack');
        }

        return $via;
    }

    public function toSlack($notifiable)
    {

        $slack = $notifiable->company->slackSetting;

        if (count($notifiable->employee) > 0 && (!is_null($notifiable->employee[0]->slack_username) && ($notifiable->employee[0]->slack_username != ''))) {
            return (new SlackMessage())
                ->from(config('app.name'))
                ->to('@' . $notifiable->employee[0]->slack_username)
                ->image($slack->slack_logo_url)
                ->content($this->attendance->user->name  . ' ' . __('modules.attendance.clockedIn') . ' ' . __('app.at') . ' '. $this->attendance->clock_in_time->timezone($this->company->timezone)->format('H:i:s'));
        }

        return (new SlackMessage())
            ->from(config('app.name'))
            ->image($slack->slack_logo_url)
            ->content($this->attendance->user->name  . ' ' . __('modules.attendance.clockedIn')  . ' ' . __('app.at') . ' '. $this->attendance->clock_in_time->timezone($this->company->timezone)->format('H:i:s'));


    }

}
