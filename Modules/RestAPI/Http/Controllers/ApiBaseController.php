<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\Company;
use Froiden\RestAPI\ApiController;
use Illuminate\Support\Facades\App;

class ApiBaseController extends ApiController
{
    public $successStatus = 200;
    public $validationStatus = 422;
    public $exceptionStatus = 409;
    public $unauthorisedStatus = 401;
    public $forbiddenStatus = 400;
    public function __construct()
    {
        parent::__construct();

        $userLocale = 'en';
        config(['auth.defaults.guard' => 'api']);

        if (!auth()->user()) {
            $setting = Company::select('locale')->first();

            if ((!is_null($setting)) && (!is_null($setting->locale))) {
                $userLocale = $setting->locale;
            }

        }
        else {
            $userLocale = ((!is_null(auth()->user()()->locale)) ? auth()->user()()->locale : 'en');
        }

        App::setLocale($userLocale);
        // SET default guard to api
        // auth('api')->user will be accessed as auth()->user();

    }
}
