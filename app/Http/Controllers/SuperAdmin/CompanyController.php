<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Role;
use App\Models\User;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Company;
use App\Models\Currency;
use App\Models\UserAuth;
use App\Models\Permission;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;
use Illuminate\Http\Request;
use App\Models\GlobalSetting;
use App\Models\CompanyAddress;
use App\Models\PermissionType;
use Illuminate\Support\Carbon;
use App\Models\EmployeeDetails;
use App\Models\UniversalSearch;
use App\Traits\CurrencyExchange;
use App\Models\SuperAdmin\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use App\Models\SuperAdmin\GlobalCurrency;
use App\Models\SuperAdmin\OfflineInvoice;
use App\Models\SuperAdmin\PackageSetting;
use App\DataTables\SuperAdmin\CompanyDataTable;
use App\DataTables\SuperAdmin\InvoiceDataTable;
use App\Http\Controllers\AccountBaseController;
use App\Notifications\SuperAdmin\CompanyApproved;
use App\Http\Requests\SuperAdmin\Company\StoreRequest;
use App\Http\Requests\SuperAdmin\Company\UpdateRequest;
use App\Http\Requests\SuperAdmin\Company\PackageUpdateRequest;

class CompanyController extends AccountBaseController
{

    use CurrencyExchange;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('superadmin.menu.companies');

        $this->middleware(function ($request, $next) {
            abort_403(GlobalSetting::validateSuperAdmin());
            return $next($request);
        });
    }

    /**
     * client list
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CompanyDataTable $dataTable)
    {
        $this->viewPermission = user()->permission('view_companies');
        abort_403(!($this->viewPermission == 'all'));

        if (!request()->ajax()) {
            $this->packages = Package::all();
        }

        $this->unapprovedCount = Company::where('approved', 0)->count();

        return $dataTable->render('super-admin.companies.index', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_companies');
        abort_403(!($this->addPermission == 'all'));

        $this->pageTitle = __('app.add') . ' ' . __('superadmin.company');

        $this->timezones = \DateTimeZone::listIdentifiers();
        $this->currencies = GlobalCurrency::all();

        $company = new Company();
        $this->fields = [];

        if (!empty($company->getCustomFieldGroupsWithFields())) {
            $this->fields = $company->getCustomFieldGroupsWithFields()->fields;
        }

        if (request()->ajax()) {
            $html = view('super-admin.companies.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'super-admin.companies.ajax.create';

        return view('super-admin.companies.create', $this->data);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return array
     */
    public function store(StoreRequest $request)
    {
        $this->addPermission = user()->permission('add_companies');
        abort_403(!($this->addPermission == 'all'));

        DB::beginTransaction();

        $company = $this->storeAndUpdate(new Company(), $request);

        $globalCurrency = GlobalCurrency::findOrFail($request->currency_id);

        $currency = Currency::where('currency_code', $globalCurrency->currency_code)
            ->where('company_id', $company->id)
            ->first();

        if (is_null($currency)) {
            $currency = $this->newCurrency($globalCurrency, $company);
        }

        $company->currency_id = $currency->id;
        $company->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $company->updateCustomFieldData($request->custom_fields_data);
        }

        $this->addUser($company, $request);

        DB::commit();

        return Reply::redirect(route('superadmin.companies.index'), __('messages.companyCreated'));
    }

    private function newCurrency($globalCurrency, $company)
    {
        $currency = new Currency();
        $currency->currency_name = $globalCurrency->currency_name;
        $currency->currency_symbol = $globalCurrency->currency_symbol;
        $currency->currency_code = $globalCurrency->currency_code;
        $currency->is_cryptocurrency = $globalCurrency->is_cryptocurrency;
        $currency->usd_price = $globalCurrency->usd_price;
        $currency->company_id = $company->id;
        $currency->save();

        return $currency;
    }

    public function storeAndUpdate(Company $company, $request)
    {
        $company->company_name = $request->company_name;
        $company->app_name = $request->company_name;
        $company->company_email = $request->company_email;
        $company->company_phone = $request->company_phone;
        $company->website = $request->website;
        $company->address = $request->address;
        $company->timezone = $request->timezone;
        $company->locale = $request->locale;
        $company->status = $request->status;

        if ($request->has('approved')) {
            $company->approved = $request->approved;
        }

        if ($request->hasFile('logo')) {
            $company->logo = Files::uploadLocalOrS3($request->logo, 'app-logo');
            $company->light_logo = $company->logo;
        }

        $company->last_updated_by = $this->user->id;

        if (module_enabled('Subdomain')) {
            $company->sub_domain = $request->sub_domain;
        }

        $company->save();

        $company->defaultAddress->update(['address' => $request->address]);

        return $company;
    }

    public function edit($id)
    {
        $this->editPermission = user()->permission('edit_companies');
        abort_403(!($this->editPermission == 'all'));

        $this->pageTitle = __('app.update') . ' ' . __('superadmin.company');
        $this->company = Company::with('defaultAddress')->findOrFail($id)->withCustomFields();
        $this->company->user = Company::firstActiveAdmin($this->company);
        $this->timezones = \DateTimeZone::listIdentifiers();
        $this->currencies = Currency::withoutGlobalScope(CompanyScope::class)->where('company_id', $this->company->id)->get();

        $this->fields = [];

        if (!empty($this->company->getCustomFieldGroupsWithFields())) {
            $this->fields = $this->company->getCustomFieldGroupsWithFields()->fields;
        }

        if (request()->ajax()) {
            $html = view('super-admin.companies.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'super-admin.companies.ajax.edit';

        return view('super-admin.companies.create', $this->data);

    }

    public function update(UpdateRequest $request, $id)
    {
        $this->editPermission = user()->permission('edit_companies');
        abort_403(!($this->editPermission == 'all'));

        $company = Company::findOrFail($id);

        DB::beginTransaction();
        $company = $this->storeAndUpdate($company, $request);

        $currency = Currency::withoutGlobalScope(CompanyScope::class)->where('id', $request->currency_id)
            ->where('company_id', $company->id)
            ->first();

        $company->currency_id = $currency->id;
        $company->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $company->updateCustomFieldData($request->custom_fields_data);
        }

        DB::commit();

        return Reply::redirect(route('superadmin.companies.index'), __('messages.companyCreated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return array
     */
    public function destroy($id)
    {
        $this->deletePermission = user()->permission('delete_companies');
        abort_403(!($this->deletePermission == 'all'));

        Company::where('id', $id)->update(['default_task_status' => null]);
        Company::destroy($id);

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('superadmin.companies.index')]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $this->company = Company::with('currency', 'package', 'approvalBy')
            ->withCount(['employees', 'fileStorage', 'clients', 'invoices', 'estimates', 'contracts', 'projects', 'tasks', 'leads', 'tickets', 'orders'])
            ->withSum('fileStorage', 'size')
            ->with(['companyAddress' => function ($query) {
                return $query->where('is_default', 1);
            }])
            ->findOrFail($id);

        $this->company->user = Company::firstActiveAdmin($this->company);

        $this->pageTitle = $this->company->company_name;

        $tab = request('tab');

        switch ($tab) {
        case 'billing':
            return $this->billing();

        default:
            $this->view = 'super-admin.companies.ajax.show';
            break;

        }


        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'company';

        return view('super-admin.companies.show', $this->data);
    }

    public function editPackage($id)
    {
        $this->pageTitle = __('app.update') . ' ' . __('superadmin.package');

        $this->company = Company::with('package')->findOrFail($id);
        $this->packages = Package::all();
        $this->currentPackage = $this->company->package;

        $this->allPackages = collect();

        $packageInfo = [];

        foreach ($this->packages as $package) {
            $packageInfo[$package->id] = [
                'monthly' => $package->monthly_price,
                'annual' => $package->annual_price
            ];

            if ($package->default !== 'no') {
                $this->allPackages->push($this->getSeparatePackage($package));
            }
            else {
                if ($package->monthly_status) {
                    $this->allPackages->push($this->getSeparatePackage($package, 'monthly'));
                }

                if ($package->annual_status) {
                    $this->allPackages->push($this->getSeparatePackage($package));
                }
            }
        }

        $this->packageInfo = $packageInfo;

        $this->view = 'super-admin.companies.ajax.edit-package';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('super-admin.companies.create', $this->data);
    }

    private function getSeparatePackage(Package $package, $type = 'annual')
    {
        $newPackage = new Package();
        $newPackage = $package->replicate();
        $newPackage->id = $package->id;
        $newPackage->type = $type;
        $days = ($type == 'monthly') ? Carbon::now()->addMonth()->diffInDays(Carbon::now()) : Carbon::now()->addYear()->diffInDays(Carbon::now());

        if ($package->default == 'trial') {
            $days = PackageSetting::first()->no_of_days;
        }

        $newPackage->days = $days;

        return $newPackage;
    }

    public function updatePackage(PackageUpdateRequest $request, $id)
    {

        $company = Company::findOrFail($id);
        $package = Package::findOrFail($request->package);

        try {
            $company->package_id = $package->id;
            $company->package_type = $request->package_type;
            $company->status = 'active';

            $payDate = $request->pay_date ? Carbon::createFromFormat($this->global->date_format, $request->pay_date) : Carbon::now();

            $company->licence_expire_on = ($company->package_type == 'monthly') ? $payDate->copy()->addMonth()->format('Y-m-d') : $payDate->copy()->addYear()->format('Y-m-d');

            $nextPayDate = $request->next_pay_date ? Carbon::createFromFormat($this->global->date_format, $request->next_pay_date) : $company->licence_expire_on;

            if ($company->isDirty('package_id') || $company->isDirty('package_type')) {
                $offlineInvoice = new OfflineInvoice();
            }
            else {
                $offlineInvoice = OfflineInvoice::where('company_id', $id)->orderBy('created_at', 'desc')->first();

                if (!$offlineInvoice) {
                    $offlineInvoice = new OfflineInvoice();
                }
            }

            $offlineInvoice->company_id = $company->id;
            $offlineInvoice->package_id = $company->package_id;
            $offlineInvoice->package_type = $request->packageType;
            $offlineInvoice->amount = ($request->amount ?: $package->{$request->packageType . '_price'}) ?: 0.00;
            $offlineInvoice->pay_date = $payDate;
            $offlineInvoice->next_pay_date = $nextPayDate;
            $offlineInvoice->status = 'paid';
            $offlineInvoice->save();
            $company->save();

            return Reply::redirect(route('superadmin.companies.index'), __('messages.packageChanged'));
        } catch (\Throwable $th) {
            return Reply::error($th->getMessage());
        }

    }

    private function addEmployeeDetails($user, $employeeRole, $companyId)
    {
        $employee = new EmployeeDetails();
        $employee->user_id = $user->id;
        $employee->company_id = $companyId;
        /* @phpstan-ignore-line */
        $employee->employee_id = 'EMP-' . $user->id;
        /* @phpstan-ignore-line */
        $employee->save();

        $search = new UniversalSearch();
        $search->searchable_id = $user->id;
        $search->company_id = $companyId;
        $search->title = $user->name;
        $search->route_name = 'employees.show';
        $search->save();

        // Assign Role
        $user->roles()->attach($employeeRole->id);
        /* @phpstan-ignore-line */
    }

    public function addUser($company, $request)
    {
        // Save Admin
        $user = User::withoutGlobalScopes([CompanyScope::class, ActiveScope::class])->where('company_id', $company->id)->where('email', $request->email)->first();

        if (is_null($user)) {
            $user = new User();
        }

        $userAuth = UserAuth::createUserAuthCredentials($request->email);

        $user->company_id = $company->id;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->status = 'active';
        $user->user_auth_id = $userAuth->id;
        $user->locale = $company->locale;
        $user->save();

        if ($request->password != '') {
            UserAuth::where('id', $user->user_auth_id)->update(['password' => bcrypt($request->password)]);
        }

        if (!$user->hasRole('admin')) {

            // Attach Admin Role
            $adminRole = Role::withoutGlobalScope(CompanyScope::class)->where('name', 'admin')->where('company_id', $company->id)->first();

            $employeeRole = Role::withoutGlobalScope(CompanyScope::class)->where('name', 'employee')->where('company_id', $user->company_id)->first();

            $user->roles()->attach($adminRole->id);
            $this->addEmployeeDetails($user, $employeeRole, $company->id);


            $allPermissions = Permission::orderBy('id')->get()->pluck('id')->toArray();
            $permissionType = PermissionType::where('name', 'all')->first();

            foreach ($allPermissions as $permission) {
                $user->permissionTypes()->attach([
                    $permission => [
                        'permission_type_id' => $permissionType->id ?? PermissionType::ALL
                    ]]);
            }
        }
    }

    public function loginAsCompany($companyId)
    {
        $company = Company::findOrFail($companyId);
        $admin = Company::firstActiveAdmin($company);

        if (!$admin) {
            return Reply::error('Impersonating this company is not possible as there is no administrator.');
        }

        $user = user();
        session()->flush();
        session()->forget('user');

        Auth::logout();
        session(['impersonate' => $user->user_auth_id]);
        session(['impersonate_company_id' => $company->id]);
        session(['user' => $admin]);

        Auth::loginUsingId($admin->user_auth_id);

        return Reply::success(__('superadmin.successfullyLoginAsCompany'));
    }

    public function billing()
    {
        $this->managePermission = user()->permission('manage_billing');
        abort_403(!($this->managePermission == 'all'));

        $dataTable = new InvoiceDataTable();
        $tab = request('tab');
        $this->activeTab = $tab ?: 'company';

        $this->view = 'super-admin.companies.ajax.billing';

        return $dataTable->render('super-admin.companies.show', $this->data);
    }

    public function ajaxLoadCompany(Request $request)
    {
        $search = $request->search;

        $companies = [];

        if ($search) {
            $companies = Company::orderby('company_name')
                ->select('id', 'company_name', 'logo', 'light_logo')
                ->where('company_name', 'like', '%' . $search . '%')
                ->take(20)
                ->get();
        }

        $response = array();

        foreach ($companies as $company) {

            $response[] = array(
                'id' => $company->id,
                'text' => $company->company_name,
                'logo_url' => $company->logo_url,
            );

        }

        return response()->json($response);
    }

    public function approveCompany()
    {
        $companyId = request()->companyId;

        $company = Company::findOrFail($companyId);
        $company->approved = 1;
        $company->save();

        $user = Company::firstActiveAdmin($company);
        $user->notify(new CompanyApproved($company));

        return Reply::success(__('superadmin.companyApprovedSuccess'));

    }

}
