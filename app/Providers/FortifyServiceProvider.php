<?php

namespace App\Providers;

use App\Actions\Fortify\AttemptToAuthenticate;
use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\RedirectIfTwoFactorConfirmed;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Company;
use App\Models\GlobalSetting;
use App\Models\LanguageSetting;
use App\Models\SocialAuthSetting;
use App\Models\SuperAdmin\FooterMenu;
use App\Models\SuperAdmin\FrontDetail;
use App\Models\SuperAdmin\FrontMenu;
use App\Models\User;
use App\Scopes\ActiveScope;
use App\Models\UserAuth;
use Carbon\Carbon;
use Exception;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Features;
use Modules\Subdomain\Http\Middleware\SubdomainCheck;
use Str;

class FortifyServiceProvider extends ServiceProvider
{

    use AppBoot;

    /**
     * Register any application services.
     *
     * @return void
     */
    // WORKSUITESAAS
    public function register()
    {

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {

            public function toResponse($request)
            {
                session(['user' => User::find(user()->id)]);

                if (auth()->user() && auth()->user()->user->is_superadmin) {
                    return redirect(RouteServiceProvider::SUPER_ADMIN_HOME);
                }

                $emailCountInCompanies = DB::table('users')->where('email', user()->email)->count();
                session()->forget('user_company_count');

                if ($emailCountInCompanies > 1) {
                    if (module_enabled('Subdomain')) {
                        UserAuth::multipleUserLoginSubdomain();
                    }
                    else {
                        session(['user_company_count' => $emailCountInCompanies]);

                        return redirect(route('superadmin.superadmin.workspaces'));
                    }

                }

                return redirect(session()->has('url.intended') ? session()->get('url.intended') : RouteServiceProvider::HOME);
            }

        });

        $this->app->instance(TwoFactorLoginResponse::class, new class implements TwoFactorLoginResponse {

            public function toResponse($request)
            {
                session(['user' => User::find(user()->id)]);

                if (auth()->user() && auth()->user()->user->is_superadmin) {
                    return redirect(RouteServiceProvider::SUPER_ADMIN_HOME);
                }

                $emailCountInCompanies = DB::table('users')->where('email', user()->email)->count();
                session(['user_company_count' => $emailCountInCompanies]);

                if ($emailCountInCompanies > 1) {
                    return redirect(route('superadmin.superadmin.workspaces'));
                }

                return redirect(session()->has('url.intended') ? session()->get('url.intended') : RouteServiceProvider::HOME);
            }

        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::authenticateThrough(function (Request $request) {

            return array_filter([
                config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorConfirmed::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]);
        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Fortify::authenticateThrough();
        Fortify::authenticateUsing(function (Request $request) {
            $rules = [
                'email' => 'required|email:rfc|regex:/(.+)@(.+)\.(.+)/i'
            ];

            $request->validate($rules);

            $userAuth = UserAuth::where('email', $request->email)->first();

            if ($userAuth && Hash::check($request->password, $userAuth->password)) {

                // Added for validation of account login in company
                UserAuth::validateLoginActiveDisabled($userAuth);

                return $userAuth;
            }
        });


        Fortify::requestPasswordResetLinkView(function () {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.passwords.forget', ['globalSetting' => $globalSetting]);
        });

        Fortify::loginView(function () {

            $this->showInstall();

            $this->checkMigrateStatus();
            $globalSetting = GlobalSetting::first();
            // Is worksuite
            $company = Company::first();


            if (!$this->isLegal()) {

                if (!module_enabled('Subdomain')){
                    return redirect('/');
                }

                // We will only show verify page for super-admin-login
                // We will check it's opened on main or not
                if (Str::contains(request()->url(), 'super-admin-login')) {
                    return redirect('/');
                }
            }

            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            $userTotal = User::count();

            if ($userTotal == 0) {
                $accountSetupBlade = 'auth.account_setup';
                // WORKSUITESAAS
                if (isWorksuiteSaas()){
                    $accountSetupBlade = 'super-admin.account_setup';
                }

                return view($accountSetupBlade, ['global' => $globalSetting, 'setting' => $globalSetting]);
            }

            $socialAuthSettings = SocialAuthSetting::first();

            $languages = language_setting();

            if ($globalSetting->front_design == 1 && $globalSetting->login_ui == 1 && !module_enabled('Subdomain')) {
                $localeLanguage = LanguageSetting::where('language_code', App::getLocale())->first();

                $frontMenuCount = FrontMenu::select('id', 'language_setting_id')->where('language_setting_id', $localeLanguage?->id)->count();
                $frontDetail = FrontDetail::first();
                $frontMenu = FrontMenu::where('language_setting_id', $frontMenuCount > 0 ? ($localeLanguage?->id) : null)->first();
                $footerMenuCount = FooterMenu::select('id', 'language_setting_id')->where('language_setting_id', $localeLanguage?->id)->count();
                $footerSettings = FooterMenu::whereNotNull('slug')->where('language_setting_id', $footerMenuCount > 0 ? ($localeLanguage?->id) : null)->get();

                if (session()->has('language')) {
                    $locale = session('language');
                }
                else {
                    $locale = $frontDetail->locale;
                }


                return view('super-admin.saas.login',
                    [
                        'setting' => $globalSetting,
                        'socialAuthSettings' => $socialAuthSettings,
                        'company' => $company,
                        'global' => $globalSetting,
                        'frontMenu' => $frontMenu,
                        'footerSettings' => $footerSettings,
                        'locale' => $locale,
                        'frontDetail' => $frontDetail,
                        'languages' => $languages,
                    ]
                );
            }

            return view('auth.login', [
                'globalSetting' => $globalSetting,
                'socialAuthSettings' => $socialAuthSettings,
                'company' => $company,
                'languages' => $languages,
            ]);

        });

        Fortify::resetPasswordView(function ($request) {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.passwords.reset-password', ['request' => $request, 'globalSetting' => $globalSetting]);
        });

        Fortify::confirmPasswordView(function ($request) {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.password-confirm', ['request' => $request, 'globalSetting' => $globalSetting]);
        });

        Fortify::twoFactorChallengeView(function () {
            $globalSetting = GlobalSetting::first();
            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

            return view('auth.two-factor-challenge', ['globalSetting' => $globalSetting]);
        });

        Fortify::registerView(function () {

            // ISWORKSUITE
            $company = Company::first();
            $globalSetting = GlobalSetting::first();

            if (!$company->allow_client_signup) {
                return redirect(route('login'));
            }

            App::setLocale($globalSetting->locale);
            Carbon::setLocale($globalSetting->locale);
            setlocale(LC_TIME, $globalSetting->locale . '_' . mb_strtoupper($globalSetting->locale));

                return view('auth.register', ['globalSetting' => $globalSetting]);

        });

        Fortify::verifyEmailView(function () {
            $userAuth = UserAuth::find(user()->user_auth_id);

            if ((!is_null($userAuth->email_code_expires_at) && $userAuth->email_code_expires_at->isPast()) || is_null($userAuth->email_code_expires_at)) {
                $userAuth->sendEmailVerificationNotification();
            }

            return view('auth.verify-email');
        });


    }

    public function checkMigrateStatus()
    {
        return check_migrate_status();
    }

}
