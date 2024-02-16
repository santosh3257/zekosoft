<?php

namespace Modules\RestAPI\Http\Requests\Invoice;

use Modules\RestAPI\Http\Requests\BaseRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class CreateRequest extends BaseRequest
{
    public function authorize()
    {
        $user = api_user();

        // Admin can add estimates
        // Or User who has role other than employee and have permission of add_estimates
        return in_array('invoices', $user->modules)
            && ($user->hasRole('admin') || ($user->user_other_role !== 'employee' && $user->cans('add_invoices')));
    }

    public function rules()
    {
        return [
            'client_id' => 'required|integer',
            'valid_till' => 'nullable|date_format:Y-m-d',
            'date_of_issue' => 'nullable|date_format:Y-m-d',
            'sub_total' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'section_name' => 'nullable|array',
            'section_text' => 'nullable|array',
            'article_id' => 'nullable|array',
            'article_name' => 'nullable|array',
            'allow_house_tax' => 'nullable|array',
            'house_work_id' => 'nullable|array',
            'quantity' => 'nullable|array',
            'unit' => 'nullable|array',
            'rate' => 'nullable|array',
            'amount' => 'nullable|array',
            'vat' => 'nullable|array',
            'account_code' => 'nullable|array',
            'total_house_service_tax' => 'nullable|numeric',
            'house_tax_total' => 'nullable|numeric',
            'total_tax' => 'nullable|numeric',
            'total_discount' => 'nullable|numeric',
            'e_sign' => 'nullable|boolean',
            'language_id' => 'nullable|integer',
            'currency_id' => 'nullable|integer',
            'template_id' => 'nullable|integer',
            'send_status' => 'nullable|in:1,0',
            'status' => 'required|in:declined,accepted,waiting,sent,draft,canceled',
            'paymentReminderDays' => 'nullable|integer',
            'paymentReminderDueDate' => "nullable|date_format:Y-m-d",
            'scheduleInvoiceDate' => "nullable|date_format:Y-m-d",
            'scheduleInvoiceTime' => "nullable|date_format:H:i",
            'attached_pdf_in_mail' => 'nullable|in:1,0',
            'lateFeeChargeType' => 'nullable|in:percentage,flat',
            'late_fee_applicable_days' => 'nullable|integer',
            'cash_invoice_type' => 'nullable|in:cash,credit card,autogiro',
        ]; 
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
