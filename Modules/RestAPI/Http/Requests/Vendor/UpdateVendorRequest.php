<?php namespace Modules\RestAPI\Http\Requests\Vendor;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateVendorRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'name' => 'required',
            'ssn' => 'required',
            'vat_number' => 'required',
            'email' => 'required|email',
            "bankgiro" => 'required',
            "bank_fee" => 'required|in:sender,receiver,both',
            "billing_address" => 'required',
            'country' => "required",
            'state' => 'required',
            'city' => 'required',
            'postal_code' => 'required',
        ];
    }

    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator) { 
        $response = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'data' => $validator->errors()
        ];
        throw new HttpResponseException(response()->json($response, 200)); 
    }


}
