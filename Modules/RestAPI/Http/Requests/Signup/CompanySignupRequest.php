<?php namespace Modules\RestAPI\Http\Requests\Signup;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

use Illuminate\Contracts\Validation\Validator;

class CompanySignupRequest extends BaseRequest
{


    public function rules()
    {
        return [
            'email' => 'required|unique:users|email|max:255',
            'password' => 'required|string|min:6|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
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
