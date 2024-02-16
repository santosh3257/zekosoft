<?php

namespace App\Traits;

use Illuminate\Support\Facades\Config;

trait GoogleOAuth
{

    public function setGoogleoAuthConfig()
    {
        $setting = global_setting();

        $domain = request()->getScheme() . '://' . (config('app.main_application_subdomain') ?: getDomain());

        Config::set('services.google.client_id', $setting->google_client_id);
        Config::set('services.google.client_secret', $setting->google_client_secret);
        Config::set('services.google.redirect_uri', $domain . '/account/settings/google-auth');
    }

}
