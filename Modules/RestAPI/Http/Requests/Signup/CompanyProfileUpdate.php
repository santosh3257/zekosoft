<?php namespace Modules\RestAPI\Http\Requests\Signup;

use Modules\RestAPI\Http\Requests\BaseRequest;

class CompanyProfileUpdate extends BaseRequest
{

    public function rules()
    {
        return [
            'company_name' => 'required|alpha',
            'company_phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'social_security' => 'required',
            'country_code' => 'required|numeric',
            'country' => 'required',
            'origination_no' => 'required',
            'vat_number' => 'required',
            'bankgigo' => 'required',
            'city'=>'required',
            'state'=>'required',
            'zipcode'=>'required',
        ];
    }

    public function authorize()
    {
        return true;
    }

}
