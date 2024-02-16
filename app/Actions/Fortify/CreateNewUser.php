<?php

namespace App\Actions\Fortify;

use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NewCustomer;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Fortify;

class CreateNewUser implements CreatesNewUsers
{

    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\UserAuth
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => 'required|min:8',
        ])->validate();

        // Checking is google recaptcha is valid
        if (global_setting()->google_recaptcha_status == 'active') {
            $gRecaptchaResponseInput = global_setting()->google_recaptcha_v3_status == 'active' ? 'g_recaptcha' : 'g-recaptcha-response';
            $gRecaptchaResponse = $input[$gRecaptchaResponseInput];
            $validateRecaptcha = $this->validateGoogleRecaptcha($gRecaptchaResponse);

            if (!$validateRecaptcha) {
                abort(403, __('auth.recaptchaFailed'));
            }
        }

        // Is worksuite
        $company = Company::first();

        $user = User::create([
            'company_id' => $company->id,
            'name' => $input['name'],
            'email' => $input['email'],
            'admin_approval' => !$company->admin_client_signup_approval,
        ]);

        $userAuth = $user->userAuth()->create(['email' => $input['email'], 'password' => bcrypt($input['password'])]);
        $user->user_auth_id = $userAuth->id;
        $user->saveQuietly();

        $data = $input;
        $data['email_notifications'] = 1;
        $user->clientDetails()->create(['company_name' => $company->company_name]);

        $role = Role::where('company_id', $company->id)->where('name', 'client')->select('id')->first();
        $user->attachRole($role->id);

        $user->insertUserRolePermission($role->id);

        $log = new AccountBaseController();

        // Log search
        $log->logSearchEntry($user->id, $user->name, 'clients.show', 'client');

        if (!is_null($user->email)) {
            $log->logSearchEntry($user->id, $user->email, 'clients.show', 'client');
        }

        if (!is_null($user->clientDetails->company_name)) {
            $log->logSearchEntry($user->id, $user->clientDetails->company_name, 'clients.show', 'client');
        }

        Notification::send(User::allAdmins($user->company->id), new NewCustomer($user));

        return $userAuth;

    }

    public function validateGoogleRecaptcha($googleRecaptchaResponse)
    {
        $secret = global_setting()->google_recaptcha_v2_status == 'active' ? global_setting()->google_recaptcha_v2_secret_key : global_setting()->google_recaptcha_v3_secret_key;

        $client = new Client();
        $response = $client->post(
            'https://www.google.com/recaptcha/api/siteverify',
            [
                'form_params' => [
                    'secret' => $secret,
                    'response' => $googleRecaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ]
            ]
        );

        $body = json_decode((string)$response->getBody());

        return $body->success;
    }

}
