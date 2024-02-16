<?php

namespace App\Http\Requests\SuperAdmin\Company;

use Illuminate\Foundation\Http\FormRequest;

class PackageUpdateRequest extends FormRequest
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
        return [
            'pay_date' => 'required',
            'package' => 'required|exists:packages,id',
            'package_type' => 'required|in:monthly,annual',
        ];
    }

}
