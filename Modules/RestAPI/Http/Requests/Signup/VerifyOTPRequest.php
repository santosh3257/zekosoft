<?php namespace Modules\RestAPI\Http\Requests\Signup;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class VerifyOTPRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'email' => 'required|email|max:255',
            'otp' => 'required|min:6|max:6',
            'verify' => 'required|in:signup,reset',
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
