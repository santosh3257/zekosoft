<?php

namespace App\Observers\SuperAdmin;

use App\Models\SuperAdmin\Package;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;

class PackageObserver
{

    public function saving(Package $package)
    {
        if ($package->is_free || $package->default === 'yes') {
            $package->monthly_status = 1;
            $package->annual_status = 1;
        }
    }

    public function updated(Package $package)
    {
        if ($package->isDirty('module_in_package')) {

            $package->companies->each(function ($company) {
                (new CompanyObserver())->updateModuleSettings($company);
            });

        }

    }

}
