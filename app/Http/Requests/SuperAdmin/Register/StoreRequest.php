<?php

namespace App\Http\Requests\SuperAdmin\Register;

use App\Models\User;
use GuzzleHttp\Client;
use App\Models\Company;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;
use Illuminate\Validation\Rule;
use App\Http\Requests\CoreRequest;
use Illuminate\Support\Facades\Validator;

class StoreRequest extends CoreRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        Validator::extend('check_superadmin', function ($attribute, $value, $parameters, $validator) {
            return !User::withoutGlobalScopes([ActiveScope::class, CompanyScope::class])
                ->where('email', $value)
                ->where('is_superadmin', 1)
                ->exists();
        });



        // This is done to remove request()->merge(['sub_domain' => $subdomain]); and
        // validate on sub_domain part
        if (module_enabled('Subdomain')) {
            if (request()->sub_domain) {
                $subdomain = str_replace('.' . getDomain(), '', request()->sub_domain);

                if (!preg_match('/^[-a-zA-Z0-9_]+$/i', $subdomain)) {
                    return [
                        'sub_domain' => 'alpha_dash',
                    ];
                }
            }
        }

        $rules = [
            'company_name' => 'required',
            'name' => 'required',
            'email' => 'required|email:rfc|regex:/(.+)@(.+)\.(.+)/i|check_superadmin',
            'sub_domain' => module_enabled('Subdomain') ? 'required|banned_sub_domain|min:4|unique:companies,sub_domain|max:50' : '',
        ];

        if (request()->has('password_confirmation')) {
            $rules['password'] = 'required|confirmed|min:8';

        } else {
            $rules['password'] = 'required|min:8';
        }

        $global = global_setting();

        if($global->google_recaptcha_v2_status == 'active'){
            $rules['g-recaptcha-response'] = 'required';
        }

        if ($global->google_recaptcha_v3_status == 'active') {
            $rules['g_recaptcha'] = Rule::prohibitedIf(function () use ($global) {
                return !$this->validateGoogleRecaptcha($global->google_recaptcha_v3_secret_key, request()->g_recaptcha);
            });
        }

        if (Company::where('company_email', '=', request()->email)->exists()) {
            $rules['email'] = 'required|email:rfc|regex:/(.+)@(.+)\.(.+)/i|unique:users,email';
        }

        $user = User::where('users.email', request()->email)->first();

        if ($user) {
            $user->hasRole('employee') ? $rules['email'] = 'required|email:rfc|regex:/(.+)@(.+)\.(.+)/i|unique:users' : '';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'email.check_superadmin' => __('superadmin.emailAlreadyExist'),
            'g-recaptcha-response.required' => __('superadmin.recaptchaInvalid'),
            'g_recaptcha.prohibited' => __('superadmin.recaptchaInvalid'),
        ];
    }

    public function prepareForValidation()
    {
        if (empty($this->sub_domain)) {
            return;
        }

        // Add servername domain suffix at the end
        $subdomain = trim($this->sub_domain, '.') . '.' . getDomain();
        $this->merge(['sub_domain' => $subdomain]);
        request()->merge(['sub_domain' => $subdomain]);
    }

    public function validateGoogleRecaptcha($secret, $googleRecaptchaResponse)
    {
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
