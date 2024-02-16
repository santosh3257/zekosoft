<?php namespace Modules\RestAPI\Http\Requests\Article;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class UpdateArticleRequest extends BaseRequest
{

    public function rules()
    {
        return [
            'name' => 'required',
            'type' => 'required|in:item,service',
            'rate' => 'required|numeric',
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
