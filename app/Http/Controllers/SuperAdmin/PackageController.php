<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Helper\Reply;
use App\Models\Module;
use App\Models\Company;
use App\Models\GlobalSetting;
use App\Models\ModuleSetting;
use App\Models\SuperAdmin\Package;
use App\Models\SuperAdmin\GlobalCurrency;
use App\Models\SuperAdmin\PackageSetting;
use App\DataTables\SuperAdmin\PackageDataTable;
use App\Http\Controllers\AccountBaseController;
use App\Http\Requests\SuperAdmin\Packages\StoreRequest;
use App\Http\Requests\SuperAdmin\Packages\UpdateRequest;
use App\Models\SuperAdmin\GlobalPaymentGatewayCredentials;

class PackageController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'superadmin.menu.packages';
        $this->global = global_setting();

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PackageDataTable $dataTable)
    {
        $this->viewPermission = user()->permission('view_packages');

        abort_403(!($this->viewPermission == 'all'));

        return $dataTable->render('super-admin.packages.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_packages');

        abort_403(!($this->addPermission == 'all'));

        $this->global = GlobalSetting::first();
        $this->paymentGateway = GlobalPaymentGatewayCredentials::first();
        $this->pageTitle = __('superadmin.packages.create');
        $this->position = Package::count();
        $this->packageModules = Module::where('module_name', '<>', 'settings')
            ->where('module_name', '<>', 'dashboards')
            ->where('module_name', '<>', 'restApi')
            ->whereNotIn('module_name', Module::disabledModuleArray())
            ->get();
        $this->currencies = GlobalCurrency::all();

        if (request()->ajax()) {
            $html = view('super-admin.packages.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'super-admin.packages.ajax.create';

        return view('super-admin.packages.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $this->addPermission = user()->permission('add_packages');

        abort_403(!($this->addPermission == 'all'));
        $request->request->add(['max_storage_size' => '500','storage_unit'=>'gb']);
        if ($request->module_in_package == null) {
            return Reply::error(__('superadmin.messages.moduleBlank'));

        }

        if ($request->has('is_recommended') && $request->is_recommended == 'on') {
            Package::where('is_recommended', 1)->update(['is_recommended' => 1]);
        }

        $data = $this->modifyRequest($request);
        Package::create($data);

        return Reply::redirect(route('superadmin.packages.index'), __('messages.packageCreated'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->editPermission = user()->permission('edit_packages');

        abort_403(!($this->editPermission == 'all'));

        $this->pageTitle = __('superadmin.packages.edit');
        $this->package = Package::findOrFail($id);

        $this->packageModules = Module::where('module_name', '<>', 'settings')
            ->where('module_name', '<>', 'dashboards')
            ->where('module_name', '<>', 'restApi')
            ->whereNotIn('module_name', Module::disabledModuleArray())
            ->get();

        $this->paymentGateway = GlobalPaymentGatewayCredentials::first();
        $this->currencies = GlobalCurrency::all();

        if ($this->package->default == 'trial') {
            $this->trial = PackageSetting::first();
        }

        if (request()->ajax()) {
            $html = view('super-admin.packages.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'super-admin.packages.ajax.edit';

        return view('super-admin.packages.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequest $request
     * @param int $id
     * @return array
     */
    public function update(UpdateRequest $request, $id)
    {
       
        $this->editPermission = user()->permission('edit_packages');

        abort_403(!($this->editPermission == 'all'));

        if ($request->module_in_package == null) {
            return Reply::error(__('superadmin.messages.moduleBlank'));

        }

        if ($request->has('is_recommended') && $request->is_recommended == 'on') {
            Package::where('is_recommended', 1)->update(['is_recommended' => 1]);
        }

        if(!$request->has('employee_unlimited')){
            $request->request->add(['employee_unlimited' => 'false']);
        }

        if(!$request->has('project_unlimited')){
            $request->request->add(['project_unlimited' => 'false']);
        }
        

        $package = Package::with('companies')->find($id);
        $data = $this->modifyRequest($request);
        $package->update($data);

        // Update if trial package is modified
        $this->updateTrialPackage($package, $request);

        ModuleSetting::whereNull('company_id')->delete();

        if ($request->has('module_in_package')) {
            $moduleInPackage = (array)json_decode($package->module_in_package);

            foreach ($package->companies as $company) {
                $this->packageModify($moduleInPackage, $company);
            }
        }

        return Reply::redirect(route('superadmin.packages.index'), __('messages.updateSuccess'));

    }

    private function updateTrialPackage($package, $request)
    {
        if ($package->default == 'trial') {
            $setting = PackageSetting::first();
            $setting->no_of_days = $request->no_of_days;
            $setting->notification_before = $request->notification_before;
            $setting->trial_message = $request->trial_message;
            $setting->status = $request->status;
            $setting->save();
        }
    }

    private function packageModify($moduleInPackage, $company)
    {
        ModuleSetting::where('company_id', $company->id)->delete();

        $clientModules = ['projects', 'tickets', 'invoices', 'estimates', 'events', 'messages', 'tasks', 'timelogs', 'contracts', 'notices', 'payments', 'Zoom', 'orders', 'knowledgebase'];

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

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->deletePermission = user()->permission('delete_projects');
        abort_403(!($this->deletePermission == 'all'));

        $package = Package::findOrFail($id);

        if ($package->default != 'no') {
            return Reply::error(__('superadmin.packages.defaultPackageCannotDelete'));
        }

        $companies = Company::where('package_id', $id)->get();

        if ($companies) {
            $defaultPackage = Package::where('default', 'yes')->first();

            if ($defaultPackage) {
                foreach ($companies as $company) {
                    ModuleSetting::where('company_id', $company->id)->delete();

                    $moduleInPackage = (array)json_decode($defaultPackage->module_in_package);

                    $clientModules = ['projects', 'tickets', 'invoices', 'estimates', 'events', 'messages', 'tasks', 'timelogs', 'contracts', 'notices', 'payments', 'Zoom', 'orders', 'knowledgebase'];

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

                    $company->package_id = $defaultPackage->id;
                    $company->save();
                }
            }
        }

        $package->delete();

        return Reply::success('messages.deleteSuccess');
    }

    private function modifyRequest($request)
    {
        $data = $request->all();
        $data['module_in_package'] = json_encode($request->module_in_package);
        $data['is_private'] = $request->has('is_private') && $request->is_private == 'true' ? 1 : 0;
        $data['is_recommended'] = $request->has('is_recommended') && $request->is_recommended == 'on' ? 1 : 0;
        $data['is_free'] = (($request->package_type == 'free') ? 1 : 0);

        $data['monthly_status'] = $request->has('monthly_status') && $request->monthly_status == 'true' ? 1 : 0;
        $data['annual_status'] = $request->has('annual_status') && $request->annual_status == 'true' ? 1 : 0;

        $data['sort'] = $request->sort;
        $data['currency_id'] = $request->currency_id;

        if ($request->has('is_free') && $request->is_free == 'true') {
            $data['monthly_price'] = 0;
            $data['annual_price'] = 0;
        }

        return $data;
    }

}
