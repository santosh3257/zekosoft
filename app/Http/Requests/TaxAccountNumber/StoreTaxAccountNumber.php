<?php

namespace App\Http\Requests\TaxAccountNumber;

use App\Http\Requests\CoreRequest;

class StoreTaxAccountNumber extends CoreRequest
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
            'account_number' => 'required',
            'tax_id' => 'required|numeric',
            'description' => 'required',
            'description_se' => 'required',
        ];
    }

}
