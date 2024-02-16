<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ModuleSetting;
use App\Scopes\CompanyScope;
use Illuminate\Database\Seeder;
use App\Observers\CompanyObserver;

class ModuleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run($companyId)
    {

        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'employee';
        $module->module_name = 'projects';
        $module->status = 'active';
        $module->save();

        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'employee';
        $module->module_name = 'messages';
        $module->status = 'active';
        $module->save();

        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'employee';
        $module->module_name = 'notices';
        $module->status = 'active';
        $module->save();

        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'employee';
        $module->module_name = 'leads';
        $module->status = 'active';
        $module->save();


        // Client Module
        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'client';
        $module->module_name = 'invoices';
        $module->status = 'active';
        $module->save();

        $module = new ModuleSetting();
        $module->company_id = $companyId;
        $module->type = 'client';
        $module->module_name = 'projects';
        $module->status = 'active';
        $module->save();

        $company = Company::withoutGlobalScope(CompanyScope::class)->findOrFail($companyId);
        $companyObserver = new CompanyObserver();
        $companyObserver->moduleSettings($company);

    }

}
