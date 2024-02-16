<?php namespace Modules\RestAPI\Http\Requests\SendInvitation;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class SendInvitationRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'role_id' => 'required|numeric',
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required|max:500',
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
