<?php

namespace App\Http\Requests\SuperAdmin\GlobalCurrency;

use App\Http\Requests\CoreRequest;

class StoreGlobalCurrency extends CoreRequest
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
            'currency_name' => 'required|unique:global_currencies,currency_name,' . $this->route('global_currency_setting'),
            'currency_symbol' => 'required',
            'usd_price' => 'required_if:is_cryptocurrency,yes',
            'exchange_rate' => 'required_if:is_cryptocurrency,no',
            'currency_code' => 'required|unique:global_currencies,currency_code,' . $this->route('global_currency_setting'),
        ];
    }

}
