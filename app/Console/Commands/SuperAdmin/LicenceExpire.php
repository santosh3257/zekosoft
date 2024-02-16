<?php

namespace App\Console\Commands\SuperAdmin;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\ModuleSetting;
use Illuminate\Console\Command;
use App\Models\SuperAdmin\Package;
use Illuminate\Support\Facades\DB;
use App\Models\SuperAdmin\OfflineInvoice;
use App\Notifications\SuperAdmin\LicenseExpire;
use App\Notifications\SuperAdmin\LicenseExpirePre;

class LicenceExpire extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licence-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set licence expire status of companies in companies table.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $companies = Company::with('package')->where('status', 'active')
            ->whereNotNull('licence_expire_on')
            ->where('licence_expire_on', '<', Carbon::now()->format('Y-m-d'))
            ->whereHas('package', function ($query) {
                $query->where('default', '!=', 'yes')->where('is_free', 0);
            })->get();

        $packages = Package::all();

        $trialPackage = $packages->filter(function ($value, $key) {
            return $value->default == 'trial';
        })->first();

        $defaultPackage = $packages->filter(function ($value, $key) {
            return $value->default == 'yes';
        })->first();

        $otherPackages = $packages->filter(function ($value, $key) {
            return $value->default == 'no';
        });

        // Set default package for license expired companies.
        foreach ($companies as $company) {

            $latestInvoice = $this->getLatestInvoice($company);

            if (!($latestInvoice && $latestInvoice->next_pay_date->format('Y-m-d') > Carbon::now()->format('Y-m-d'))) {
                ModuleSetting::where('company_id', $company->id)->delete();

                $moduleInPackage = (array)json_decode($defaultPackage->module_in_package);
                $clientModules = ['clients', 'projects', 'tickets', 'invoices', 'estimates', 'events', 'tasks', 'messages', 'payments', 'contracts', 'notices', 'timelogs', 'orders', 'knowledgebase',];

                if ($moduleInPackage) {
                    foreach ($moduleInPackage as $module) {

                        if (in_array($module, $clientModules)) {
                            $moduleSetting = new ModuleSetting();
                            $moduleSetting->company_id = $company->id;
                            $moduleSetting->module_name = $module;
                            $moduleSetting->status = 'active';
                            $moduleSetting->type = 'client';
                            $moduleSetting->save();
                        }

                        $moduleSetting = new ModuleSetting();
                        $moduleSetting->company_id = $company->id;
                        $moduleSetting->module_name = $module;
                        $moduleSetting->status = 'active';
                        $moduleSetting->type = 'employee';
                        $moduleSetting->save();

                        $moduleSetting = new ModuleSetting();
                        $moduleSetting->company_id = $company->id;
                        $moduleSetting->module_name = $module;
                        $moduleSetting->status = 'active';
                        $moduleSetting->type = 'admin';
                        $moduleSetting->save();
                    }
                }

                $company->package_id = $defaultPackage->id;
                $company->licence_expire_on = null;
                $company->status = 'license_expired';
                $company->save();

                $companyUser = Company::firstActiveAdmin($company);
                $companyUser->notify(new LicenseExpire($company));
            }

        }

        // Sent notification to companies before license expire.
        foreach ($otherPackages as $package) {
            if(!is_null($package->notification_before)) {
                $companiesNotify = Company::with('package')
                    ->where('status', 'active')
                    ->whereNotNull('licence_expire_on')
                    ->where('licence_expire_on', '<', Carbon::now()->addDays($package->notification_before)->format('Y-m-d'))
                    ->whereHas('package', function ($query) use ($package) {
                        $query->where('default', '!=', 'yes')->where('is_free', 0)->where('id', $package->id);
                    })->get();

                foreach ($companiesNotify as $cmp) {
                    $companyUser = Company::firstActiveAdmin($cmp);
                    $companyUser->notify(new LicenseExpirePre($cmp));
                }
            }
        }
    }

    protected function getLatestInvoice($company)
    {
        $stripe = DB::table('stripe_invoices')
            ->join('packages', 'packages.id', 'stripe_invoices.package_id')
            ->join('companies', 'companies.id', 'stripe_invoices.company_id')
            ->selectRaw('stripe_invoices.id, stripe_invoices.invoice_id ,companies.company_name as company,
            packages.name as package, stripe_invoices.transaction_id, "Stripe" as method,stripe_invoices.amount,
            stripe_invoices.pay_date as paid_on ,stripe_invoices.next_pay_date,"" as offline_method_id,stripe_invoices.created_at')
            ->whereNotNull('stripe_invoices.pay_date')->where('company_id', $company->id);

        $razorpay = DB::table('razorpay_invoices')
            ->join('packages', 'packages.id', 'razorpay_invoices.package_id')
            ->join('companies', 'companies.id', 'razorpay_invoices.company_id')
            ->selectRaw('razorpay_invoices.id ,razorpay_invoices.invoice_id , companies.company_name as company,
             packages.name as name, razorpay_invoices.transaction_id, "Razorpay" as method,razorpay_invoices.amount, razorpay_invoices.pay_date as paid_on ,
             razorpay_invoices.next_pay_date,"" as offline_method_id,razorpay_invoices.created_at')
            ->whereNotNull('razorpay_invoices.pay_date')->where('company_id', $company->id);

        $paystack = DB::table('paystack_invoices')
            ->join('packages', 'packages.id', 'paystack_invoices.package_id')
            ->join('companies', 'companies.id', 'paystack_invoices.company_id')
            ->selectRaw('paystack_invoices.id ,"" as invoice_id , companies.company_name as company,
             packages.name as name, paystack_invoices.transaction_id, "Paystack" as method,paystack_invoices.amount, paystack_invoices.pay_date as paid_on ,
             paystack_invoices.next_pay_date,"" as offline_method_id,paystack_invoices.created_at')
            ->whereNotNull('paystack_invoices.pay_date')->where('company_id', $company->id);


        $authorize = DB::table('authorize_invoices')
            ->join('packages', 'packages.id', 'authorize_invoices.package_id')
            ->join('companies', 'companies.id', 'authorize_invoices.company_id')
            ->selectRaw('authorize_invoices.id ,"" as invoice_id , companies.company_name as company,
             packages.name as name, authorize_invoices.transaction_id, "Authorize" as method,authorize_invoices.amount, authorize_invoices.pay_date as paid_on ,
             authorize_invoices.next_pay_date,"" as offline_method_id,authorize_invoices.created_at')
            ->whereNotNull('authorize_invoices.pay_date')->where('company_id', $company->id);

        $mollie = DB::table('mollie_invoices')
            ->join('packages', 'packages.id', 'mollie_invoices.package_id')
            ->join('companies', 'companies.id', 'mollie_invoices.company_id')
            ->selectRaw('mollie_invoices.id ,"" as invoice_id , companies.company_name as company,
             packages.name as name, mollie_invoices.transaction_id, "Mollie" as method,mollie_invoices.amount, mollie_invoices.pay_date as paid_on ,
             mollie_invoices.next_pay_date,"" as offline_method_id,mollie_invoices.created_at')
            ->whereNotNull('mollie_invoices.pay_date')->where('company_id', $company->id);

        $paypal = DB::table('paypal_invoices')
            ->join('packages', 'packages.id', 'paypal_invoices.package_id')
            ->join('companies', 'companies.id', 'paypal_invoices.company_id')
            ->selectRaw('paypal_invoices.id,"" as invoice_id, companies.company_name as company,
                packages.name as package, paypal_invoices.transaction_id,
             "Paypal" as method , paypal_invoices.total as amount, paypal_invoices.paid_on,
             paypal_invoices.next_pay_date,"" as offline_method_id,paypal_invoices.created_at')
            ->where('paypal_invoices.status', 'paid')->where('company_id', $company->id);


        $payfast = DB::table('payfast_invoices')
            ->join('packages', 'packages.id', 'payfast_invoices.package_id')
            ->join('companies', 'companies.id', 'payfast_invoices.company_id')
            ->selectRaw('payfast_invoices.id,"" as invoice_id,companies.company_name as company,
            packages.name as package, payfast_invoices.m_payment_id as transaction_id,"PayFast" as method, payfast_invoices.amount as amount,
            payfast_invoices.pay_date as paid_on ,payfast_invoices.next_pay_date,"" as offline_method_id,payfast_invoices.created_at')
            ->whereNotNull('payfast_invoices.pf_payment_id')->where('payfast_invoices.status', 'paid')->where('company_id', $company->id);

        $offline = OfflineInvoice::join('packages', 'packages.id', 'offline_invoices.package_id')
            ->join('companies', 'companies.id', 'offline_invoices.company_id')
            ->selectRaw('offline_invoices.id,"" as invoice_id,
             companies.company_name as company, packages.name as package, offline_invoices.transaction_id,
              "Offline" as method ,offline_invoices.amount as amount, offline_invoices.pay_date as paid_on,
              offline_invoices.next_pay_date,offline_invoices.offline_method_id,offline_invoices.created_at')
            ->with('offlinePaymentMethod')
            ->where('offline_invoices.status', 'paid')->where('company_id', $company->id);

        return $offline->union($paypal)
            ->union($paystack)
            ->union($mollie)
            ->union($authorize)
            ->union($stripe)
            ->union($razorpay)
            ->union($payfast)
            ->latest()
            ->first();
    }

}
