<?php

namespace App\Http\Requests\SuperAdmin\Company;

use App\Models\CustomField;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
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

        $len = strlen(getDomain()) + 4;

        $rules = [
            'company_name' => 'required',
            'company_email' => 'required|email|unique:companies,company_email,' . $this->route('company'),
            'sub_domain' => module_enabled('Subdomain') ? 'required|min:4|max:50|banned_sub_domain|min:' . $len . '|unique:companies,sub_domain,' . $this->route('company') : '',
            'address' => 'required',
            'status' => 'required'
        ];


        if (request()->get('custom_fields_data')) {
            $fields = request()->get('custom_fields_data');

            foreach ($fields as $key => $value) {
                $idArray = explode('_', $key);
                $id = end($idArray);
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
