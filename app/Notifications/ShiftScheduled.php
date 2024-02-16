<?php

namespace App\Notifications;

use App\Models\EmployeeShiftSchedule;

class ShiftScheduled extends BaseNotification
{


    public $employeeShiftSchedule;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(EmployeeShiftSchedule $employeeShiftSchedule)
    {
        $this->employeeShiftSchedule = $employeeShiftSchedule;
        $this->company = $this->employeeShiftSchedule->shift->company;
    }

    public function via()
    {
        return ['mail', 'database'];
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
        $url = route('dashboard');
        $url = getDomainSpecificUrl($url, $this->company);

        $content = __('app.date') . ': ' . $this->employeeShiftSchedule->date->toFormattedDateString() . '<br>' . __('modules.attendance.shiftName') . ': ' . $this->employeeShiftSchedule->shift->shift_name . '<br>';

        return $build
            ->subject(__('email.shiftScheduled.subject') . ' - ' . config('app.name') . '.')
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.loginDashboard'),
                'notifiableName' => $notifiable->name
            ]);
    }

    public function toArray()
    {
        return [
            'user_id' => $this->employeeShiftSchedule->user_id,
            'shift_id' => $this->employeeShiftSchedule->employee_shift_id,
            'date' => $this->employeeShiftSchedule->date
        ];
    }

}
