<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class DailyTimeLogReport extends Mailable implements ShouldQueue
{

    use Queueable, SerializesModels;

    public $todayDate;
    public $company;
    public $username;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Company $company, $username)
    {
        $this->todayDate = now()->timezone($company->timezone)->format('Y-m-d');
        $this->company = $company;
        $this->username = $username;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('email.dailyTimelogReport.subject') . ' ' . $this->todayDate)
            ->markdown('mail.timelog.timelog-report', ['date' => $this->todayDate, 'name' => $this->username]);
    }

    public function attachments()
    {
        return [
            Attachment::fromData(fn() => $this->domPdfObjectForDownload()['pdf']->output(), 'TimeLog-Report-' . $this->todayDate . '.pdf')
                ->withMime('application/pdf'),
        ];
    }

    public function domPdfObjectForDownload()
    {
        $company = $this->company;
        $employees = User::select('users.id', 'users.name')
            ->with(['timeLogs' => function ($query) use ($company) {
                $query->whereRaw('DATE(start_time) = ?', [$this->todayDate]);
                $query->where('company_id', $company->id);
            }, 'timeLogs.breaks'])
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')->onlyEmployee()
            ->where('roles.company_id', $company->id)
            ->groupBy('users.id');

        $employees = $employees->get();

        $employeeData = [];

        foreach ($employees as $employee) {
            $employeeData[$employee->name] = [];
            $employeeData[$employee->name]['timelog'] = 0;
            $employeeData[$employee->name]['timelogBreaks'] = 0;

            if (count($employee->timeLogs) > 0) {

                foreach ($employee->timeLogs as $timeLog) {
                    $employeeData[$employee->name]['timelog'] += $timeLog->total_minutes;

                    if (count($timeLog->breaks) > 0) {
                        foreach ($timeLog->breaks as $timeLogBreak) {
                            $employeeData[$employee->name]['timelogBreaks'] += $timeLogBreak->total_minutes;
                        }
                    }
                }
            }
        }

        $now = $this->todayDate;

        $pdf = app('dompdf.wrapper')->setPaper('A4', 'landscape');

        $options = $pdf->getOptions();
        $options->set(array('enable_php' => true));
        $pdf->getDomPDF()->setOptions($options);

        $pdf->loadView('timelog-report', ['employees' => $employeeData, 'date' => $now, 'company' => $company]);

        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);

        $filename = 'timelog-report';

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

}
