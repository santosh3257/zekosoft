<?php

namespace App\Http\Requests\SuperAdmin\Company;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        \Illuminate\Support\Facades\Validator::extend('check_superadmin', function ($attribute, $value, $parameters, $validator) {
            return !\App\Models\User::withoutGlobalScopes([\App\Scopes\ActiveScope::class, \App\Scopes\CompanyScope::class])
                ->where('email', $value)
                ->where('is_superadmin', 1)
                ->exists();
        });

        $len = strlen(getDomain()) + 4;

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
            'company_email' => 'required|email|unique:companies',
            'address' => 'required',
            'sub_domain' => module_enabled('Subdomain') ? 'required|banned_sub_domain|min:' . $len . '|unique:companies,sub_domain|max:50' : '',
            'status' => 'required',
            'email' => 'required|email|check_superadmin',
            'name' => 'required|min:2',

        ];

        if (request()->get('custom_fields_data')) {
            $fields = request()->get('custom_fields_data');

            foreach ($fields as $key => $value) {
                $idarray = explode('_', $key);
                $id = end($idarray);
                $customField = CustomField::findOrFail($id);

                if ($customField->required == 'yes' && (is_null($value) || $value == '')) {
                    $rules['custom_fields_data[' . $key . ']'] = 'required';
                }
            }
        }

        return $rules;

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

    public function messages()
    {
        return [
            'email.check_superadmin' => __('superadmin.emailAlreadyExist'),
        ];
    }

    public function attributes()
    {
        $attributes = [];

        if (request()->get('custom_fields_data')) {
            $fields = request()->get('custom_fields_data');

            foreach ($fields as $key => $value) {
                $idarray = explode('_', $key);
                $id = end($idarray);
                $customField = CustomField::findOrFail($id);

                if ($customField->required == 'yes') {
                    $attributes['custom_fields_data[' . $key . ']'] = $customField->label;
                }
            }
        }

        return $attributes;
    }

}
