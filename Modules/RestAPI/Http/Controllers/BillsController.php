<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Http\Controllers\BaseController;
use Modules\RestAPI\Entities\Bill;
use Modules\RestAPI\Entities\Product;
use Modules\RestAPI\Entities\BillItems;
use Modules\RestAPI\Entities\AddBillPayment;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Helper\Files;
use Validator;
use Modules\RestAPI\Entities\Vendor;
use Carbon\Carbon;

class BillsController extends ApiBaseController {

    protected $model = '';

    //Get All Bills
    public function allBills(Request $request){
        $user = auth()->user()->user;
        $query = Bill::with('billItems','vendorInfo')->where('added_by',$user->id);
        if(!empty($request->status)){
            $query->where('status', 'LIKE', $request->status);
        }
        if(!empty($request->bill_no)){
            $query->where('bill_no', 'LIKE', $request->bill_no);
        }
        if(!empty($request->ocr_no)){
            $query->where('ocr_no', 'LIKE', $request->ocr_no);
        }
        if(!empty($request->vendor_id)){
            $query->where('vendor_id', $request->vendor_id);
        }
        if(!empty($request->vendor_name)){
            $search = $request->vendor_name;
            $query->where(function ($query) use ($search) {
                    $query->whereHas('vendorInfo', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
                });
        }
        if($request->archive == 1){
            $query->where('archive', 1);
        }
        else{
            $query->where('archive', 0);
        }
        $bills = $query->paginate($request->per_page);
        $data = array(
            "status" => true,
            'bills' => $bills
            
        );
        return Response()->json($data, $this->successStatus);
    }

    public function addMyBill(Request $request){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $bill_no = $this->generateBillNR($user->company_id);
            $insertBill = new Bill();
            $insertBill->added_by = $user->id;
            $insertBill->vendor_id = $request->vendor_id;
            $insertBill->bill_no = $bill_no;
            $insertBill->ocr_no = $request->ocr_no;
            $insertBill->date_of_issue = $request->date_of_issue;
            $insertBill->due_date = $request->due_date;
            $insertBill->total_amount = $request->total_amount;
            $insertBill->sub_total = $request->sub_total;
            $insertBill->vat_tax_amount = $request->vat_tax_amount;
            $imageName = null;
           if ($request->hasFile('bill_attachment')) {
                Files::createDirectoryIfNotExist('bill/attachments');
                $imageName = Files::uploadLocalOrS3($request->bill_attachment, 'bill/attachments/', 300);
            } 
            $insertBill->bill_attachment = $imageName;
            $insertBill->language_id = $request->language_id;
            $insertBill->currency_id = $request->currency_id;
            $insertBill->save();

            if(!empty($request->article_id)){
                $articles= $request->article_id;
                if(count($articles) > 0 && $articles[0] !=''){
                    $article_ids = $request->article_id;
                    $notes = $request->note;
                    $quantitys = $request->quantity;
                    $units = $request->unit;
                    $rates = $request->rate;
                    $amounts = $request->amount;
                    $tax_amounts = $request->tax_amount;
                    $tax_ids = $request->tax_id;
                    $account_code_ids = $request->account_code_id;
                    foreach($quantitys as $idx=>$quanty){
                        if($quanty !='' && $quanty !=null){
                            $billItems = new BillItems();
                            $billItems->bill_id = $insertBill->id;
                            if (array_key_exists($idx, $article_ids))
                            {
                                $billItems->article_id = $article_ids[$idx];
                            }
                            if (array_key_exists($idx, $notes))
                            {
                                $billItems->note = $notes[$idx];
                            }
                            if (array_key_exists($idx, $quantitys))
                            {
                                $billItems->quantity = $quantitys[$idx];
                            }
                            if (array_key_exists($idx, $units))
                            {
                                $billItems->unit = $units[$idx];
                            }
                            if (array_key_exists($idx, $rates))
                            {
                                $billItems->rate = $rates[$idx];
                            }
                            if (array_key_exists($idx, $amounts))
                            {
                                $billItems->amount = $amounts[$idx];
                            }
                            if (array_key_exists($idx, $tax_amounts))
                            {
                                $billItems->tax_amount = $tax_amounts[$idx];
                            }
                            if (array_key_exists($idx, $tax_ids))
                            {
                                $billItems->tax_id = $tax_ids[$idx];
                            }
                            
                            if (array_key_exists($idx, $account_code_ids))
                            {
                                $billItems->account_code_id = $account_code_ids[$idx];
                            }
                            $billItems->save();
                        }
                    }

                }
                
            }

            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Your Bill created successfully',
                
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
    public function editMyBill(Request $request, $id){
        $user = auth()->user()->user;
        $bill = Bill::with('billItems')->where('added_by',$user->id)->where('id',$id)->first();
        if($bill){

        $data = array(
            "status" => true,
            'bill' => $bill
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Vendor bill not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    public function viewMyBill(Request $request, $id){
        $user = auth()->user()->user;
        $bill = Bill::with('billItems.articalInfo','billItems.taxInfo','vendorInfo','billPayments')->where('added_by',$user->id)->where('id',$id)->first();
        if($bill){

        $data = array(
            "status" => true,
            'bill' => $bill
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Vendor bill not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }

    public function updateVendorBill(Request $request, $id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $getBill = Bill::findOrFail($id);
                if($getBill){
                $getBill->added_by = $user->id;
                $getBill->vendor_id = $request->vendor_id;
                $getBill->bill_no = $request->bill_no;
                $getBill->ocr_no = $request->ocr_no;
                $getBill->date_of_issue = $request->date_of_issue;
                $getBill->due_date = $request->due_date;
                $getBill->total_amount = $request->total_amount;
                $getBill->sub_total = $request->sub_total;
                $getBill->vat_tax_amount = $request->vat_tax_amount;
                if ($request->hasFile('bill_attachment')) {
                    Files::createDirectoryIfNotExist('bill/attachments');
                    $imageName = Files::uploadLocalOrS3($request->bill_attachment, 'bill/attachments/', 300);
                    $getBill->bill_attachment = $imageName;
                }
                else{
                    $getBill->bill_attachment =  $getBill->bill_attachment;

                }
                $getBill->language_id = $request->language_id;
                $getBill->currency_id = $request->currency_id;
                $getBill->save();
                if(!empty($request->article_id)){
                    $billItemsdelete = BillItems::where('bill_id',$id)->delete();
                    $articles= $request->article_id;
                    if(count($articles) > 0 && $articles[0] !=''){
                        $article_ids = $request->article_id;
                        $notes = $request->note;
                        $quantitys = $request->quantity;
                        $units = $request->unit;
                        $rates = $request->rate;
                        $amounts = $request->amount;
                        $tax_amounts = $request->tax_amount;
                        $tax_ids = $request->tax_id;
                        $account_code_ids = $request->account_code_id;
                        foreach($quantitys as $idx=>$quanty){
                            if($quanty !='' && $quanty !=null){
                                $billItems = new BillItems();
                                $billItems->bill_id = $id;
                                if (array_key_exists($idx, $article_ids))
                                {
                                    $billItems->article_id = $article_ids[$idx];
                                }
                                if (array_key_exists($idx, $notes))
                                {
                                    $billItems->note = $notes[$idx];
                                }
                                if (array_key_exists($idx, $quantitys))
                                {
                                    $billItems->quantity = $quantitys[$idx];
                                }
                                if (array_key_exists($idx, $units))
                                {
                                    $billItems->unit = $units[$idx];
                                }
                                if (array_key_exists($idx, $rates))
                                {
                                    $billItems->rate = $rates[$idx];
                                }
                                if (array_key_exists($idx, $amounts))
                                {
                                    $billItems->amount = $amounts[$idx];
                                }
                                if (array_key_exists($idx, $tax_amounts))
                                {
                                    $billItems->tax_amount = $tax_amounts[$idx];
                                }
                                if (array_key_exists($idx, $tax_ids))
                                {
                                    $billItems->tax_id = $tax_ids[$idx];
                                }
                                
                                if (array_key_exists($idx, $account_code_ids))
                                {
                                    $billItems->account_code_id = $account_code_ids[$idx];
                                }
                                $billItems->save();
                            }
                        }

                    }
                    
                }
                DB::commit();
                $data = array(
                    "status" => true,
                    'message' => 'Vendor Bill update successfully'
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Vendor bill not found'
                    
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

    public function deleteVendorBill(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                Bill::where('added_by',$user->id)->findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'The vendor bill has been deleted successfully'                
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

    public function changeBillStatus(Request $request, $id){
        $user = auth()->user()->user;
        if(isset($request->status) && !empty($request->status)){
            $validator = Validator::make($request->all(), [ 
                'status' => 'required|in:paid,unpaid,overdue,pending payment,draft',
            ]);
            
        }
        else{
            $validator = Validator::make($request->all(), [ 
                'archive' => 'required|in:0,1',
            ]);
        }

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
       $bill = Bill::where('added_by',$user->id)->findOrFail($id);
       if($bill){
            if(isset($request->status) && !empty($request->status)){
                $bill->status = $request->status;
                $bill->save();
                
                $data = array(
                    "status" => true,
                    'message' => 'The status has been changed successfully. Thanks'                
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $bill->archive = $request->archive;
                $bill->save();
                if($request->archive == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'Your bill has been archived successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'Your bill has been removed from arcived successfully. Thanks'                
                    );
                }
                return Response()->json($data, $this->successStatus);
            }

        }
        else{
            $data = array(
                "status" => false,
                'message' => "Bill not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }

    }
    public function allVendorNameWithId(Request $request){
        
        $user = auth()->user()->user;
        $newArray = array();
        $vanderDatas = Vendor::select('id','name')->where('company_id',$user->company_id)->get();
        $i = 0;
        foreach($vanderDatas as $vanderData){
            $newArray[$i]["id"]= $vanderData->id;
            $newArray[$i]["text"]= $vanderData->name;
            $i++;
        }
        $bill_no = $this->generateBillNR($user->company_id);
        $data = array(
            "status" => true,
            'vendors' => $newArray,
            'bill_no'=>$bill_no,
            
        );
        return Response()->json($data, $this->successStatus);
    }
    public function generateBillNR($company_id)
    {
        
        $billObj = Bill::select('bill_no')->latest('id')->first();
        if ($billObj) {
            $billNr = $billObj->bill_no;
            $generateBill_nr = str_pad($billNr + 1, 8, "0", STR_PAD_RIGHT);
        } else {
            $generateBill_nr = str_pad($company_id, 8, "0", STR_PAD_RIGHT);
        }
        return $generateBill_nr;
    }

    public function addBillPayment(Request $request){
        try{
            
            DB::beginTransaction();
            $user = auth()->user()->user;
            $bill = Bill::where('added_by',$user->id)->findOrFail($request->bill_id);
            $total_bill_amount = $bill->total_amount;
            $billPayments = AddBillPayment::where('bill_id',$request->bill_id)->get();
            $total_payment = 0;
            foreach($billPayments as $key=>$billPayment){
                $total_payment = $total_payment + $billPayment->payment_amount;
            }
            $validator = Validator::make($request->all(), [ 
                'payment_amount' => 'required',
                'payment_date' => 'required',
                'payment_method' => 'required',
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $newAmount = $total_payment + $request->payment_amount;
            if($total_bill_amount >= $newAmount){
                $billPayment = new AddBillPayment();
                $billPayment->bill_id = $request->bill_id;
                $billPayment->bill_no = $request->bill_no;
                $billPayment->payment_amount = $request->payment_amount;
                $billPayment->payment_date = $request->payment_date;
                $billPayment->payment_method = $request->payment_method;
                $billPayment->payment_notes = $request->payment_notes;
                $billPayment->save();
                if($total_bill_amount == $newAmount){
                    $bill->status = 'paid';
                    $bill->save();
                }
                else{
                    $bill->status = 'unpaid';
                    $bill->save();
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

    public function editBillPayment(Request $request, $id){
        $billPayment = AddBillPayment::where('id',$id)->first();
        if($billPayment){

        $data = array(
            "status" => true,
            'billPaymentDetails' => $billPayment
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'bill payment not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }
        
    }
    public function updateBillPayment(Request $request,$id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $getBillPayment = AddBillPayment::findOrFail($id);
            $billPayments = AddBillPayment::where('bill_id',$getBillPayment->bill_id)->get();
            $bill = Bill::where('added_by',$user->id)->findOrFail($getBillPayment->bill_id);
            $total_bill_amount = $bill->total_amount;
            $total_payment = 0;
            foreach($billPayments as $key=>$billPayment){
                $total_payment = $total_payment + $billPayment->payment_amount;
            }
            if($getBillPayment){
                $newAmount = $total_payment + $request->payment_amount - $getBillPayment->payment_amount;
                if($total_bill_amount >= $newAmount){
                    $getBillPayment->payment_amount = $request->payment_amount;
                    $getBillPayment->payment_date = $request->payment_date;
                    $getBillPayment->payment_method = $request->payment_method;
                    $getBillPayment->payment_notes = $request->payment_notes;
                    $getBillPayment->save();
                    if($total_bill_amount == $newAmount){
                        $bill->status = 'paid';
                        $bill->save();
                    }
                    else{
                        $bill->status = 'unpaid';
                        $bill->save();
                    }
                    DB::commit();
                    $data = array(
                        "status" => true,
                        'message' => 'Bill payment update successfully'
                        
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
                    'message' => 'bill payment not found'
                    
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
    public function deleteBillPayment(Request $request){
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                AddBillPayment::findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'Your Bill payment has been deleted successfully'                
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
    public function getAllBillPaymentDetails(Request $request){
        $user = auth()->user()->user;
        $totalBillAmount = 0;
        $paid_amount = 0;
        $total_outstanding = 0;
        $total_overdue = 0;
        $total_draft = 0;
        $BillDatas = Bill::with('billPayments')->where('added_by',$user->id)->get();
        foreach($BillDatas as $key=>$BillData){
            $totalBillAmount = $totalBillAmount + $BillData->total_amount;
            $billPayments = $BillData->billPayments;
            $date = new Carbon;
            if(count($billPayments) > 0){
                foreach($billPayments as $billPayment){
                        $amount_tobe_paid = 0;
                        $paid_amount = $paid_amount + $billPayment->payment_amount;
                        $amount_tobe_paid = $amount_tobe_paid + $billPayment->payment_amount;
                }
                if($BillData->status == 'draft'){
                    $total_draft = $total_draft + ($BillData->total_amount - $amount_tobe_paid);
                    
                }
                if($date > $BillData->due_date){
                    $total_overdue = $total_overdue +  ($BillData->total_amount - $paid_amount);
                }
            }
            else{
                //return 'check';
                if($date > $BillData->due_date){
                    $total_overdue = $total_overdue +  $BillData->total_amount;
                }
                if($BillData->status == 'draft'){
                    $total_draft = $total_draft + $BillData->total_amount;
                } 
            }

        }
        $total_outstanding = $totalBillAmount-$paid_amount;
        $billAmountData = array(
            "total-amount" =>$totalBillAmount,
            "paid-amount" =>$paid_amount,
            "total-outstanding" =>$total_outstanding,
            "total-overdue"=>$total_overdue,
            "total-draft"=>$total_draft,
            
        );
        $data = array(
            "status" => true,
            'billsData' => $billAmountData
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function getAllBillPayments(Request $request){
        $user = auth()->user()->user;
        //$BillDatas = Bill::with('billPayments')->where('added_by',$user->id)->get();
        $BillDatas =Bill::join('add_bill_payments', 'bills.id', '=', 'add_bill_payments.bill_id')
        ->select('add_bill_payments.id','add_bill_payments.bill_no','add_bill_payments.bill_id','add_bill_payments.payment_amount', 'add_bill_payments.payment_date','add_bill_payments.payment_method','add_bill_payments.payment_notes')
        ->where('added_by',$user->id)
        ->orderBy('id','asc')->get();
        $data = array(
            "status" => true,
            'payment' => $BillDatas
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function getAllBillPaymentsById(Request $request,$id){
        $user = auth()->user()->user;
        $billPayment = AddBillPayment::where('bill_id',$id);
        if(!empty($request->search)){
            $billPayment->where('bill_no',$request->search);
        }
        $getBillPayment = $billPayment->orderBy('id','asc')->paginate($request->per_page);
        $data = array(
            "status" => true,
            'payment' => $getBillPayment
            
        );
        return Response()->json($data, $this->successStatus);

    }

}
