<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\GlobalSetting;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Routing\Controller;

class AppController extends Controller
{
    public function app()
    {
        $setting = GlobalSetting::select('global_app_name', 'logo')->first();
        $setting->company_name = $setting->global_app_name;

        return ApiResponse::make('Application data fetched successfully', $setting->toArray());
    }
}
