<?php

namespace Modules\RestAPI\Http\Requests\Projects;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CreateRequest extends BaseRequest
{
    /**
     * @return bool
     *
     * @throws \Froiden\RestAPI\Exceptions\UnauthorizedException
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

        $rules = [
            'project_name' => 'required',
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d',
            'project_type' => 'required|in:fixed,hourly',
            'total_budget' => 'required|numeric',
            'total_hours' => 'nullable|numeric',
            'status' => 'required|in:active,finished,cancelled',
        ];

        // if (! $this->has('without_deadline')) {
        //     $rules['deadline'] = 'required';
        // }

        if ($this->project_budget != '') {
            // $rules['project_budget'] = 'numeric';
            $rules['currency.id'] = 'sometimes|exists:currencies,id';
        }

        return $rules;
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
