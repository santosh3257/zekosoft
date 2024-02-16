<?php namespace Modules\RestAPI\Http\Requests\Signup;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ResendOTPRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'email' => 'required|email|max:255',
            'otp_type' => 'required|in:signup,reset'
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
