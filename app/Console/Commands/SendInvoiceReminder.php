<?php

namespace App\Console\Commands;

use App\Events\InvoiceReminderAfterEvent;
use App\Events\InvoiceReminderEvent;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendInvoiceReminder extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-invoice-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send invoice reminder to the client before and after due date of invoice';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $companies = Company::with('currency')->get();

        foreach ($companies as $company) {
            $invoice_setting = InvoiceSetting::where('company_id', $company->id)->first();

            $invoices = Invoice::whereNotNull('due_date')
                ->where('status', '!=', 'paid')
                ->where('status', '!=', 'canceled')
                ->where('status', '!=', 'draft')
                ->where('company_id', $company->id);


            if ($invoice_setting->send_reminder != 0) {
                $invoices = $invoices
                    ->whereDate('due_date', Carbon::now($company->timezone)->addDays($invoice_setting->send_reminder))
                    ->get();

                foreach ($invoices as $invoice) {
                    $notifyUser = $invoice->client;

                    if (!is_null($notifyUser)) {
                        event(new InvoiceReminderEvent($invoice, $notifyUser, $invoice_setting->send_reminder));
                    }
                }
            }

            if ($invoice_setting->reminder == 'after') {
                $invoices_after = $invoices
                    ->whereDate('due_date', Carbon::now($company->timezone)->subDays($invoice_setting->send_reminder_after))
                    ->get();

                foreach ($invoices_after as $invoice) {
                    $notifyUser = $invoice->client;

                    if (!is_null($notifyUser)) {
                        event(new InvoiceReminderAfterEvent($invoice, $notifyUser, $invoice_setting->send_reminder_after));
                    }

                }

            }
            else {
                $invoices_every = $invoices
                    ->whereDate('due_date', '<', now($company->timezone))
                    ->get();

                foreach ($invoices_every as $invoice) {
                    $notifyUser = $invoice->client;
                    $date_diff = $invoice->due_date->diffInDays(now());

                    if ($invoice_setting->send_reminder_after != 0) {
                        if ($date_diff % $invoice_setting->send_reminder_after == 0 && !is_null($notifyUser)) {
                            event(new InvoiceReminderAfterEvent($invoice, $notifyUser, $invoice_setting->send_reminder_after));
                        }
                    }

                }

            }


        }
    }

}
