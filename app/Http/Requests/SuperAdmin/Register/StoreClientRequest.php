<?php

namespace App\Http\Requests\SuperAdmin\Register;

use App\Models\Company;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        $company = Company::where('hash', request()->company_hash)->firstOrFail();

        return [
            'name' => 'required|string|max:255',
            // 'email' => 'required|string|email|max:255|unique:users',
            'email' => 'required|email:rfc|unique:users,email,null,id,company_id,' . $company->id,
            'password' => 'required|min:8',
        ];
    }

}
