<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Events\NewInvoiceEvent;
use App\Events\PaymentReminderEvent;
use Froiden\RestAPI\ApiResponse;
use Modules\RestAPI\Entities\Invoice;
use Modules\RestAPI\Http\Requests\Invoice\CreateRequest;
use Modules\RestAPI\Http\Requests\Invoice\DeleteRequest;
use Modules\RestAPI\Http\Requests\Invoice\IndexRequest;
use Modules\RestAPI\Http\Requests\Invoice\ShowRequest;
use Modules\RestAPI\Http\Requests\Invoice\UpdateRequest;
use Modules\RestAPI\Entities\User;
use Modules\RestAPI\Entities\Product;
use Modules\RestAPI\Entities\Project;
use App\Models\HouseService;
use App\Models\Tax;
use App\Models\TaxAccountNumber;
use App\Models\VatTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Carbon\Carbon;
use App\Models\InvoiceItems;
use App\Models\VatPercentage;
use Modules\RestAPI\Entities\SettingInvoice;
use App\Helper\Files;
use Modules\RestAPI\Entities\InvoicePayment;
use Modules\RestAPI\Entities\InvoiceEmail;
use Modules\RestAPI\Entities\TaxReduction;
use App\Scopes\ActiveScope;

class InvoiceController extends ApiBaseController
{
    

    public function create(){
        try{
            $user = auth()->user()->user;
            // Active Clients
            $activeClients = User::allActiveClients();
            if($activeClients){
                foreach($activeClients as $key=>$client){
                    $activeClients[$key]->text = $client->name;
                }
            }
            
            // Active Articles
            $products = Product::select('id','name','price','taxes','account_code','in_stock')->where('company_id',$user->company_id)->get();
            if($products){
                foreach($products as $key=>$product){
                    $products[$key]->text = $product->name;
                }
            }

            // House Services
            $houseServices = HouseService::with('works')->get();
            if($houseServices){
                foreach($houseServices as $key=>$service){
                    $houseServices[$key]->text = $service->service_name;
                    if(!empty($service->works)){
                        foreach($service->works as $idx=>$work){
                            $houseServices[$key]->works[$idx]->text = $work->work_name;
                        }
                    }
                }
            }

            // Vat Types
            $vatTypes = VatTypes::where('status','')->get();
            if($vatTypes){
                foreach($vatTypes as $key=>$type){
                    $vatTypes[$key]->text = $type->vat_type;
                }
            }
            // Taxes
            $vatPercentage = VatPercentage::get();
            // Account Codes
            $accountCodes = TaxAccountNumber::where('status','active')->orderBy('account_number','asc')->get();

            
            $lastNumber = Invoice::lastCompanyInvoiceNumber($user->company_id) + 1;
            
            $data = array(
                "status" => true,
                'clients' => $activeClients,
                'products' => $products,
                'vatPercentage' => $vatPercentage,
                'accountCodes' => $accountCodes,
                'houseServices' => $houseServices,
                'vatTypes' => $vatTypes,
                'lastNumber' => $lastNumber,
                
            );
            return Response()->json($data, $this->successStatus);
        } catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Some error occurred when retrive the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // Get Projects Associated With Client
    public function getProjectAssociatedWithCLient($client_id){
        $user = auth()->user()->user;
        $projects = Project::where('company_id',$user->company_id)->where('client_id',$client_id)->select('id','company_id','project_name','client_id')->get();
        $data = array(
            "status" => true,
            'projects' => $projects,
            
        );
        return Response()->json($data, $this->successStatus);
    }
    // Save Invoice 
    public function saveInvoice(CreateRequest $request){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $invoice_number = Invoice::lastCompanyInvoiceNumber($user->company_id) + 1;
            // $invoiceValid = Invoice::where('company_id',$user->company_id)->where('invoice_number',$request->invoice_number)->first();
            // if($invoiceValid){
            //     $data = array(
            //         "status" => false,
            //         'message' => "Invoice number is already exist.",
                    
            //     );
            //     return Response()->json($data, $this->successStatus); 
            // }

            $invoice = new Invoice();
            $invoice->company_id = $user->company_id;
            $invoice->project_id = $request->project_id ?? null;
            $invoice->client_id = ($request->client_id) ?: null;
            $invoice->ocr_number = $request->ocr_number;
            $invoice->reference = $request->reference;
            $invoice->vat_type_id = $request->vat_type_id;
            $invoice->payment_days = $request->paymentDay;
            $invoice->house_service_id = $request->house_service_id;
            $invoice->house_tax_total = $request->house_tax_total;
            $invoice->due_date = $request->due_date;
            $invoice->issue_date = $request->date_of_issue;
            $invoice->status = $request->status;
            $invoice->sub_total = round($request->sub_total, 2);
            $invoice->discount = round($request->total_discount, 2);
            $invoice->discount_type = $request->discount_type;
            $invoice->discount_percentage = $request->discount;
            $invoice->total = round($request->total_amount, 2);
            $invoice->due_amount = round($request->total_amount, 2);
            $invoice->tatal_tax = round($request->tatal_tax, 2);
            if(isset($request->currency_id)){
                $invoice->currency_id = $request->currency_id;  
            }
            else{
                $invoice->currency_id = company()->currency_id; 
            }
            $invoice->default_currency_id = company()->currency_id;
            $invoice->language_id = $request->language_id;
            $invoice->exchange_rate = $request->exchange_rate;
            $invoice->recurring = 'no';
            $invoice->discount_type = 'percent';
            $invoice->show_shipping_address = 'no';
            $invoice->billing_frequency = $request->recurring_payment == 'yes' ? $request->billing_frequency : null;
            $invoice->billing_interval = $request->recurring_payment == 'yes' ? $request->billing_interval : null;
            $invoice->billing_cycle = $request->recurring_payment == 'yes' ? $request->billing_cycle : null;
            $invoice->note = trim_editor($request->add_note);
            $invoice->description = trim_editor($request->term_condition);
            $invoice->invoice_number = $invoice_number;
            $invoice->company_address_id = $request->company_address_id;
            $invoice->estimate_id = $request->estimate_id ? $request->estimate_id : null;
            $invoice->from_company_name = $request->from_company_name;
            $invoice->from_address1 = $request->from_address1;
            $invoice->from_address2 = $request->from_address2;
            $invoice->from_phone = $request->from_phone;
            $invoice->to_client_name = $request->to_client_name;
            $invoice->to_address1 = $request->to_address1;
            $invoice->to_address2 = $request->to_address2;
            $invoice->to_phone = $request->to_phone;
            $invoice->bank_account_id = $request->bank_account_id;
            $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
            if(!empty($request->file)){
                $file = $request->file('file');
                $newName = $file->hashName(); // Setting hashName name
                $file->move(storage_path('app/public/invoice-files'), $newName);
                $invoice->file = $newName;
            }
            $invoice->save();

            $settingInvoice = new SettingInvoice();
            $settingInvoice->invoice_id = $invoice->id;
            $settingInvoice->payment_reminder_status = $request->paymentReminder;
            $settingInvoice->days = $request->paymentReminderDays;
            $settingInvoice->action_due_date = $request->actionDueDate;
            $settingInvoice->recurring = $request->recurring;
            $settingInvoice->recurring_duration = $request->recurring_duration;
            $settingInvoice->recurring_next_issue_date = $request->recurring_next_issue_date;
            $settingInvoice->recurring_no_of_invoices = $request->recurring_no_of_invoices;
            $settingInvoice->scheduled_invoice_date = $request->scheduleInvoiceDate;
            $settingInvoice->scheduled_invoice_time = $request->scheduleInvoiceTime;
            $settingInvoice->attached_pdf = $request->attached_pdf_in_mail;
            $settingInvoice->late_fee_type = $request->lateFeeChargeType;
            $settingInvoice->late_fee_rate = $request->late_fee_rate;
            $settingInvoice->late_fee_days = $request->late_fee_applicable_days;
            $settingInvoice->cash_invoice_type = $request->cash_invoice_type;
            $settingInvoice->template_color = $request->template_color;
            $settingInvoice->save();

            if(!empty($request->quantity)){
                $quantity = $request->quantity;
                if(count($quantity) > 0 && $quantity[0] !=''){
                    $articleName = $request->article_name;
                    $allowHouseTax = $request->allow_house_tax;
                    $houseWorkId = $request->house_work_id;
                    $unit = $request->unit;
                    $rate = $request->rate;
                    $tax_amount = $request->tax_amount;
                    $amount = $request->amount;
                    $vat = $request->vat;
                    $accountCode = $request->account_code;
                    $article_id = $request->article_id;
                    foreach($quantity as $idx=>$quanty){
                        if($quanty !='' && $quanty !=null){
                            $invoiceItem = new InvoiceItems();
                            $invoiceItem->invoice_id = $invoice->id;
                            if (array_key_exists($idx, $houseWorkId))
                            {
                                $invoiceItem->house_work_id = $houseWorkId[$idx];
                            }
                            if (array_key_exists($idx, $articleName))
                            {
                                $invoiceItem->item_name = $articleName[$idx];
                            }
                            if (array_key_exists($idx, $allowHouseTax))
                            {
                                $invoiceItem->house_work_tax_applicable = $allowHouseTax[$idx] == true ? 1 : 0;
                            }
                            
                            $invoiceItem->quantity = $quanty;
                        
                            if (array_key_exists($idx, $rate))
                            {
                                $invoiceItem->unit_price = $rate[$idx];
                            }
                            if (array_key_exists($idx, $amount))
                            {
                                $invoiceItem->amount = $amount[$idx];
                            }
                            if (array_key_exists($idx, $tax_amount))
                            {
                                $invoiceItem->tax_amount = $tax_amount[$idx];
                            }
                            if (array_key_exists($idx, $article_id))
                            {
                                $invoiceItem->product_id = $article_id[$idx];
                            }
                            if (array_key_exists($idx, $vat))
                            {
                                $invoiceItem->tax_id = $vat[$idx];
                            }
                            if (array_key_exists($idx, $accountCode))
                            {
                                $invoiceItem->account_code_id = $accountCode[$idx];
                            }
                            if (array_key_exists($idx, $unit))
                            {
                                $invoiceItem->unit = $unit[$idx];
                            }
                            $invoiceItem->save();
                        }
                    }

                }
                
            }
            //code for add add reduction tax
            $invoiceCoapplicant = Invoice::where('added_by',$user->id)->findOrFail($invoice->id);
            if(!empty($request->co_applicant_name)){
                $applicant_name = $request->co_applicant_name;
                if(count($applicant_name) > 0 && $applicant_name[0] !=''){
                $co_applicant_id = $request->co_applicant_id;
                $co_applicant_name = $request->co_applicant_name;
                $co_applicant_social_security_no = $request->co_applicant_social_security_no;
                $tax_reduction = $request->tax_reduction;
                /* $total_tax_red = 0; */
                foreach($co_applicant_name as $idx=>$applicantData){
                    if($applicantData !='' && $applicantData !=null){
                        /* $total_tax_red = $total_tax_red + $tax_reduction[$idx]; */
                        $co_applicantItem = new TaxReduction();
                        $co_applicantItem->client_id = $request->client_id;
                        $co_applicantItem->invoice_id = $invoice->id;
                        if (array_key_exists($idx, $co_applicant_id))
                        {
                            $co_applicantItem->co_applicant_id = $co_applicant_id[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_name))
                        {
                            $co_applicantItem->co_applicant_name = $co_applicant_name[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_social_security_no))
                        {
                            $co_applicantItem->co_applicant_social_security_no = $co_applicant_social_security_no[$idx];
                        }
                        if (array_key_exists($idx, $tax_reduction))
                        {
                            $co_applicantItem->tax_reduction = $tax_reduction[$idx];
                        }
                        $co_applicantItem->save();
                        } 
                    }
                    //add some data in invoice
                    $invoiceCoapplicant->total_tax_reduction = $request->total_reduction;
                    $invoiceCoapplicant->possible_tax_reduction = $invoiceCoapplicant->house_tax_total;
                    $invoiceCoapplicant->not_applied_for_tax_reduction = 0.00;
                    $invoiceCoapplicant->determined_tax_reduction = 0.00;
                    $invoiceCoapplicant->save();
                } 
            }
            //code end for add reduction tax
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Invoice has been created successfully',
                
            );
        return Response()->json($data, $this->successStatus);

        } catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    //GET MY ALL INVOICE
    public function myAllInvoice(Request $request){
        $user = auth()->user()->user;
        $query = Invoice::join('users', 'users.id', '=', 'invoices.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'invoices.currency_id')->where('invoices.company_id',$user->company_id);
            if(!empty($request->client_name)){
                $query->where('users.name', 'LIKE', '%'.$request->client_name.'%');
            }
            if(!empty($request->number)){
                $query->where('invoices.invoice_number', $request->number);
            }
            if(!empty($request->status)){
                $query->where('invoices.status', $request->status);
            }
            if(!empty($request->archive)){
                $query->where('invoices.is_archived', $request->archive);
            }
            else{
                $query->where('invoices.is_archived', 0);
            }
            $invoices = $query->select('invoices.id','invoices.client_id','users.name','invoices.invoice_number','invoices.issue_date','invoices.due_date','invoices.total','invoices.status','invoices.is_archived','currencies.currency_symbol')->orderBy('invoices.id','desc')->paginate($request->per_page);
            $data = array(
                "status" => true,
                'invoices' => $invoices
                
            );
            return Response()->json($data, $this->successStatus);
    }
    //Get Recent invoice
    public function recentInvoice(Request $request){
        $user = auth()->user()->user;
        $query = Invoice::join('users', 'users.id', '=', 'invoices.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'invoices.currency_id')->where('invoices.company_id',$user->company_id);
            if(!empty($request->status)){
                $query->where('invoices.status', $request->status);
            }
            if(!empty($request->archive)){
                $query->where('invoices.is_archived', $request->archive);
            }
            else{
                $query->where('invoices.is_archived', 0);
            }
            $invoices = $query->select('invoices.id','invoices.client_id','users.name','invoices.invoice_number','invoices.issue_date','invoices.due_date','invoices.total','invoices.status','invoices.is_archived','currencies.currency_symbol')->orderBy('invoices.id','desc')->take(5)->get();
            $data = array(
                "status" => true,
                'invoices' => $invoices
                
            );
            return Response()->json($data, $this->successStatus);
    }
    // GET SINGLE INVOICE DATA RETURN
    public function editMyInvoice($invoice_id){
       $invoice = Invoice::with('houseService','items.product','items.houseWork','items.TaxInfo','items.accountCode','co_applicant')->where('id',$invoice_id)->first();    
       if($invoice){
            // Active Clients
            $activeClients = User::allActiveClients();
            if($activeClients){
                foreach($activeClients as $key=>$client){
                    $activeClients[$key]->text = $client->name;
                }
            }
            
            // Active Articles
            $products = Product::select('id','name','price','taxes','account_code','in_stock')->where('company_id',$invoice->company_id)->get();
            if($products){
                foreach($products as $key=>$product){
                    $products[$key]->text = $product->name;
                }
            }

            // House Services
            $houseServices = HouseService::with('works')->get();
            if($houseServices){
                foreach($houseServices as $key=>$service){
                    $houseServices[$key]->text = $service->service_name;
                    if(!empty($service->works)){
                        foreach($service->works as $idx=>$work){
                            $houseServices[$key]->works[$idx]->text = $work->work_name;
                        }
                    }
                }
            }
            $invoiceSetting = SettingInvoice::where('invoice_id',$invoice_id)->first();
            // Vat Types
            $vatTypes = VatTypes::where('status','')->get();
            if($vatTypes){
                foreach($vatTypes as $key=>$type){
                    $vatTypes[$key]->text = $type->vat_type;
                }
            }
            // Taxes
            $taxes = VatPercentage::get();
            // Account Codes
            $accountCodes = TaxAccountNumber::where('status','active')->orderBy('account_number','asc')->get();
            
            $data = array(
                "status" => true,
                'invoice' => $invoice,
                'clients' => $activeClients,
                'products' => $products,
                'taxes' => $taxes,
                'accountCodes' => $accountCodes,
                'houseServices' => $houseServices,
                'vatTypes' => $vatTypes,
                'invoiceSetting' => $invoiceSetting,
                
            );
            return Response()->json($data, $this->successStatus);
       }
    }

    //UPDATE INVOICE
    public function updateInvoice(Request $request, $invoice_id){
        try{
            $user = auth()->user()->user;
            $invoice = Invoice::where('id',$invoice_id)->first(); 
            if($invoice){

                $invoice->project_id = $request->project_id ?? null;
                $invoice->client_id = ($request->client_id) ?: null;
                $invoice->ocr_number = $request->ocr_number;
                $invoice->reference = $request->reference;
                $invoice->vat_type_id = $request->vat_type_id;
                $invoice->payment_days = $request->paymentDay;
                $invoice->house_service_id = $request->house_service_id;
                $invoice->house_tax_total = $request->house_tax_total;
                $invoice->due_date = $request->due_date;
                $invoice->issue_date = $request->date_of_issue;
                $invoice->status = $request->status;
                $invoice->sub_total = round($request->sub_total, 2);
                $invoice->discount = round($request->total_discount, 2);
                $invoice->discount_type = $request->discount_type;
                $invoice->discount_percentage = $request->discount;
                $invoice->total = round($request->total_amount, 2);
                $invoice->due_amount = round($request->total_amount, 2);
                $invoice->tatal_tax = round($request->tatal_tax, 2);
                if(isset($request->currency_id)){
                    $invoice->currency_id = $request->currency_id;  
                }
                else{
                    $invoice->currency_id = company()->currency_id; 
                }
                $invoice->default_currency_id = company()->currency_id;
                $invoice->language_id = $request->language_id;
                $invoice->exchange_rate = $request->exchange_rate;
                $invoice->recurring = 'no';
                $invoice->discount_type = 'percent';
                $invoice->show_shipping_address = 'no';
                $invoice->billing_frequency = $request->recurring_payment == 'yes' ? $request->billing_frequency : null;
                $invoice->billing_interval = $request->recurring_payment == 'yes' ? $request->billing_interval : null;
                $invoice->billing_cycle = $request->recurring_payment == 'yes' ? $request->billing_cycle : null;
                $invoice->note = trim_editor($request->add_note);
                $invoice->description = trim_editor($request->term_condition);
                $invoice->company_address_id = $request->company_address_id;
                $invoice->estimate_id = $request->estimate_id ? $request->estimate_id : null;
                $invoice->from_company_name = $request->from_company_name;
                $invoice->from_address1 = $request->from_address1;
                $invoice->from_address2 = $request->from_address2;
                $invoice->from_phone = $request->from_phone;
                $invoice->to_client_name = $request->to_client_name;
                $invoice->to_address1 = $request->to_address1;
                $invoice->to_address2 = $request->to_address2;
                $invoice->to_phone = $request->to_phone;
                $invoice->bank_account_id = $request->bank_account_id;
                $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
                if(!empty($request->file)){
                    $file = $request->file('file');
                    $newName = $file->hashName(); // Setting hashName name
                    $file->move(storage_path('app/public/invoice-files'), $newName);
                    $invoice->file = $newName;
                }
                $invoice->save();

                $settingInvoice =  SettingInvoice::where('invoice_id',$invoice_id)->first();
                $settingInvoice->payment_reminder_status = $request->paymentReminder;
                $settingInvoice->days = $request->paymentReminderDays;
                $settingInvoice->action_due_date = $request->actionDueDate;
                $settingInvoice->recurring = $request->recurring;
                $settingInvoice->recurring_duration = $request->recurring_duration;
                $settingInvoice->recurring_next_issue_date = $request->recurring_next_issue_date;
                $settingInvoice->recurring_no_of_invoices = $request->recurring_no_of_invoices;
                $settingInvoice->scheduled_invoice_date = $request->scheduleInvoiceDate;
                $settingInvoice->scheduled_invoice_time = $request->scheduleInvoiceTime;
                $settingInvoice->attached_pdf = $request->attached_pdf_in_mail;
                $settingInvoice->late_fee_type = $request->lateFeeChargeType;
                $settingInvoice->late_fee_rate = $request->late_fee_rate;
                $settingInvoice->late_fee_days = $request->late_fee_applicable_days;
                $settingInvoice->cash_invoice_type = $request->cash_invoice_type;
                $settingInvoice->save();

                if(!empty($request->quantity)){
                    $quantity = $request->quantity;
                    if(count($quantity) > 0 && $quantity[0] !=''){
                        InvoiceItems::where('invoice_id',$invoice_id)->delete();
                        $articleName = $request->article_name;
                        $allowHouseTax = $request->allow_house_tax;
                        $houseWorkId = $request->house_work_id;
                        $unit = $request->unit;
                        $rate = $request->rate;
                        $tax_amount = $request->tax_amount;
                        $amount = $request->amount;
                        $vat = $request->vat;
                        $accountCode = $request->account_code;
                        $article_id = $request->article_id;
                        foreach($quantity as $idx=>$quanty){
                            if($quanty !='' && $quanty !=null){
                                $invoiceItem = new InvoiceItems();
                                $invoiceItem->invoice_id = $invoice->id;
                                if (array_key_exists($idx, $houseWorkId))
                                {
                                    $invoiceItem->house_work_id = $houseWorkId[$idx];
                                }
                                if (array_key_exists($idx, $articleName))
                                {
                                    $invoiceItem->item_name = $articleName[$idx];
                                }
                                if (array_key_exists($idx, $allowHouseTax))
                                {
                                    $invoiceItem->house_work_tax_applicable = $allowHouseTax[$idx] == true ? 1 : 0;
                                }
                                
                                $invoiceItem->quantity = $quanty;
                            
                                if (array_key_exists($idx, $rate))
                                {
                                    $invoiceItem->unit_price = $rate[$idx];
                                }
                                if (array_key_exists($idx, $amount))
                                {
                                    $invoiceItem->amount = $amount[$idx];
                                }
                                if (array_key_exists($idx, $tax_amount))
                                {
                                    $invoiceItem->tax_amount = $tax_amount[$idx];
                                }
                                if (array_key_exists($idx, $article_id))
                                {
                                    $invoiceItem->product_id = $article_id[$idx];
                                }
                                if (array_key_exists($idx, $vat))
                                {
                                    $invoiceItem->tax_id = $vat[$idx];
                                }
                                if (array_key_exists($idx, $accountCode))
                                {
                                    $invoiceItem->account_code_id = $accountCode[$idx];
                                }
                                if (array_key_exists($idx, $unit))
                                {
                                    $invoiceItem->unit = $unit[$idx];
                                }
                                $invoiceItem->save();
                            }
                        }
    
                    }
                    else{
                        InvoiceItems::where('invoice_id',$invoice_id)->delete();
                    }
                    
                }
                 //code for add add reduction tax
            if(!empty($request->co_applicant_name)){
                $applicant_name = $request->co_applicant_name;
                if(count($applicant_name) > 0 && $applicant_name[0] !=''){
                TaxReduction::where('invoice_id',$invoice_id)->delete();
                $co_applicant_id = $request->co_applicant_id;
                $co_applicant_name = $request->co_applicant_name;
                $co_applicant_social_security_no = $request->co_applicant_social_security_no;
                $tax_reduction = $request->tax_reduction;
                /* $total_tax_red = 0; */
                foreach($co_applicant_name as $idx=>$applicantData){
                    if($applicantData !='' && $applicantData !=null){
                        /* $total_tax_red = $total_tax_red + $tax_reduction[$idx]; */
                        $co_applicantItem = new TaxReduction();
                        $co_applicantItem->client_id = $invoice->client_id;
                        $co_applicantItem->invoice_id = $invoice->id;
                        if (array_key_exists($idx, $co_applicant_id))
                        {
                            $co_applicantItem->co_applicant_id = $co_applicant_id[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_name))
                        {
                            $co_applicantItem->co_applicant_name = $co_applicant_name[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_social_security_no))
                        {
                            $co_applicantItem->co_applicant_social_security_no = $co_applicant_social_security_no[$idx];
                        }
                        if (array_key_exists($idx, $tax_reduction))
                        {
                            $co_applicantItem->tax_reduction = $tax_reduction[$idx];
                        }
                        $co_applicantItem->save();
                        } 
                    }
                    //add some data in invoice
                    $invoice->total_tax_reduction = $request->total_reduction;
                    $invoice->possible_tax_reduction = $invoice->house_tax_total;
                    $invoice->not_applied_for_tax_reduction = 0.00;
                    $invoice->determined_tax_reduction = 0.00;
                    $invoice->save();
                }
                else{
                    TaxReduction::where('invoice_id',$invoice_id)->delete();
                } 
            }
            //code end for add reduction tax
                $data = array(
                    "status" => true,
                    'message' => 'Invoice has been updated successfully',
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "No record found"
                    
                );
                return Response()->json($data, $this->successStatus); 
            }
        } catch (ApiException $e) {
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            $data = array(
                "status" => false,
                'message' => "Some error occurred when updating the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    //DELETE INVOICE
    public function deleteInvoice($invoice_id){
        $user = auth()->user()->user;
        $invoice = Invoice::where('company_id',$user->company_id)->findOrFail($invoice_id)->first();
        if($invoice){
            Invoice::findOrFail($invoice_id)->delete();
            $data = array(
                "status" => true,
                'message' => 'The invoice has been deleted successfully'                
            );
            
            return Response()->json($data, $this->successStatus);
        }
    }
    //function for change status of invoice like Archive 0 or 1 and status 'Active','Inactive'

    public function updateStatusInvoice(Request $request, $id){
        $user = auth()->user()->user;
        if((isset($request->archive) && !empty($request->archive)) || $request->archive == 0){
            $validator = Validator::make($request->all(), [ 
                'archive' => 'required|in:0,1',
            ]);
            
        }
        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
       $invoice = Invoice::where('company_id',$user->company_id)->findOrFail($id);
       if($invoice){
            if((isset($request->archive) && !empty($request->archive)) || $request->archive == 0){
                $invoice->is_archived = $request->archive;
                $invoice->save();
                
                if($request->archive == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'The invoice has been archived successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'The invoice has been removed from archived successfully. Thanks'                
                    );
                }
                return Response()->json($data, $this->successStatus);
            }
            

        }
        else{
            $data = array(
                "status" => false,
                'message' => "Invoice not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }

    }
    public function addInvoicePayment(Request $request){
        try{
            
            DB::beginTransaction();
            $user = auth()->user()->user;
            $invoice = Invoice::where('added_by',$user->id)->findOrFail($request->invoice_id);
            $total_invoice_amount = $invoice->total;
            $invoicePayments = InvoicePayment::where('invoice_id',$request->invoice_id)->get();
            $total_payment = 0;
            foreach($invoicePayments as $key=>$invoicePayment){
                $total_payment = $total_payment + $invoicePayment->payment_amount;
            }
            $validator = Validator::make($request->all(), [ 
                'payment_amount' => 'required',
                'payment_date' => 'required',
                'payment_type' => 'required',
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $newAmount = $total_payment + $request->payment_amount;
            if($total_invoice_amount >= $newAmount){
                $invPayment = new InvoicePayment();
                $invPayment->invoice_id = $request->invoice_id;
                $invPayment->invoice_no = $request->invoice_no;
                $invPayment->payment_amount = $request->payment_amount;
                $invPayment->payment_date = $request->payment_date;
                $invPayment->payment_type = $request->payment_type;
                $invPayment->payment_notes = $request->payment_notes;
                $invPayment->save();
                if($total_invoice_amount == $newAmount){
                    $invoice->status = 'paid';
                    $invoice->save();
                }
                else{
                    $invoice->status = 'unpaid';
                    $invoice->save();
                }

            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Payment cannot exceed the amount due.',
                    
                );
                return Response()->json($data, $this->successStatus);

            }
            
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Payment added successfully',
                
            );
            return Response()->json($data, $this->successStatus);

        } catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }
    public function editInvoicePayment(Request $request, $id){
        $invoicePayment = InvoicePayment::where('id',$id)->first();
        if($invoicePayment){

        $data = array(
            "status" => true,
            'invoicePaymentDetails' => $invoicePayment
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'invoice payment not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }
        
    }
    public function updateInvoicePayment(Request $request,$id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $getInvoicePayment = InvoicePayment::findOrFail($id);
            $invoicePayments = InvoicePayment::where('invoice_id',$getInvoicePayment->invoice_id)->get();
            $invoice = Invoice::where('added_by',$user->id)->findOrFail($getInvoicePayment->invoice_id);
            $total_invoice_amount = $invoice->total;
            $total_payment = 0;
            foreach($invoicePayments as $key=>$invoicePayment){
                $total_payment = $total_payment + $invoicePayment->payment_amount;
            }
            if($getInvoicePayment){
                $newAmount = $total_payment + $request->payment_amount - $getInvoicePayment->payment_amount;
                if($total_invoice_amount >= $newAmount){
                    $getInvoicePayment->payment_amount = $request->payment_amount;
                    $getInvoicePayment->payment_date = $request->payment_date;
                    $getInvoicePayment->payment_type = $request->payment_type;
                    $getInvoicePayment->payment_notes = $request->payment_notes;
                    $getInvoicePayment->save();
                    if($total_invoice_amount == $newAmount){
                        $invoice->status = 'paid';
                        $invoice->save();
                    }
                    else{
                        $invoice->status = 'unpaid';
                        $invoice->save();
                    }
                    DB::commit();
                    $data = array(
                        "status" => true,
                        'message' => 'Invoice payment update successfully'
                        
                    );
                    return Response()->json($data, $this->successStatus);

                }
                else{
                    $data = array(
                        "status" => false,
                        'message' => 'Payment cannot exceed the amount due.',
                        
                    );
                    return Response()->json($data, $this->successStatus);

                }
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'invoice payment not found'
                    
                );
                return Response()->json($data, $this->successStatus);

            }
        } catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
        

    }
    public function deleteInvoicePayment(Request $request){
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                InvoicePayment::findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'Your Invoice payment has been deleted successfully'                
            );
            
            return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'The ID is required to delete it'                
            );
            
            return Response()->json($data, $this->successStatus);
        }

    }
    public function getAllInvoicePayments(Request $request){
        $user = auth()->user()->user;
        //$BillDatas = Bill::with('billPayments')->where('added_by',$user->id)->get();
        $InvoiceDatas =Invoice::join('invoice_payments', 'invoices.id', '=', 'invoice_payments.invoice_id')
        ->select('invoice_payments.id','invoice_payments.invoice_no','invoice_payments.invoice_id','invoice_payments.payment_amount', 'invoice_payments.payment_date','invoice_payments.payment_type','invoice_payments.payment_notes')
        ->where('added_by',$user->id)
        ->orderBy('id','asc')->get();
        $data = array(
            "status" => true,
            'payment' => $InvoiceDatas
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function getAllInvoicePaymentsById(Request $request,$id){
        $user = auth()->user()->user;
        $invoicePayment = InvoicePayment::where('invoice_id',$id)->orderBy('id','asc')->paginate($request->per_page);
        $data = array(
            "status" => true,
            'invoicePayment' => $invoicePayment
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function sendInvoiceByEmail(Request $request){
        try{
                $user = auth()->user()->user;
                $invoice = Invoice::where('added_by',$user->id)->findOrFail($request->invoice_id);
                $notifyUser = User::withoutGlobalScope(ActiveScope::class)->findOrFail($request->client_id);
                event(new NewInvoiceEvent($invoice,$notifyUser));
                $data = array(
                    "status" => true,
                    'message' => 'Mail send successfully!'                
                );
                return Response()->json($data, $this->successStatus);
            }catch (\Exception $e) {
                $data = array(
                    "status" => false,
                    'message' => "Some error occurred when create PDF. Please try again or contact support.",
                    'error' => $e->getMessage()
                    
                );
                return Response()->json($data, $this->successStatus); 
            }
            
        
    }
    public function addTaxReduction(Request $request){
        //TaxReduction
         //add client co-applicant
         try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $invoice = Invoice::where('added_by',$user->id)->findOrFail($request->invoice_id);
            if(!empty($request->co_applicant_name)){
                $applicant_name = $request->co_applicant_name;
                if(count($applicant_name) > 0 && $applicant_name[0] !=''){
                $co_applicant_id = $request->co_applicant_id;
                $co_applicant_name = $request->co_applicant_name;
                $co_applicant_social_security_no = $request->co_applicant_social_security_no;
                $tax_reduction = $request->tax_reduction;
                /* $total_tax_red = 0; */
                foreach($co_applicant_name as $idx=>$applicantData){
                    if($applicantData !='' && $applicantData !=null){
                        /* $total_tax_red = $total_tax_red + $tax_reduction[$idx]; */
                        $co_applicantItem = new TaxReduction();
                        $co_applicantItem->client_id = $request->client_id;
                        $co_applicantItem->invoice_id = $request->invoice_id;
                        if (array_key_exists($idx, $co_applicant_id))
                        {
                            $co_applicantItem->co_applicant_id = $co_applicant_id[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_name))
                        {
                            $co_applicantItem->co_applicant_name = $co_applicant_name[$idx];
                        }
                        if (array_key_exists($idx, $co_applicant_social_security_no))
                        {
                            $co_applicantItem->co_applicant_social_security_no = $co_applicant_social_security_no[$idx];
                        }
                        if (array_key_exists($idx, $tax_reduction))
                        {
                            $co_applicantItem->tax_reduction = $tax_reduction[$idx];
                        }
                        $co_applicantItem->save();
                        } 
                    }
                    //add some data in invoice
                    $invoice->total_tax_reduction = $request->total_reduction;
                    $invoice->possible_tax_reduction = $invoice->house_tax_total;
                    $invoice->not_applied_for_tax_reduction = 0.00;
                    $invoice->determined_tax_reduction = 0.00;
                    $invoice->save();
                } 
            }
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Tax reduction add successfully',
                
            );
            return Response()->json($data, $this->successStatus);
        }catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } 
        

    }

}
