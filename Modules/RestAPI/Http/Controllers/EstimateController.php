<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Events\NewEstimateEvent;
use Froiden\RestAPI\ApiResponse;
use App\Models\Estimate;
use App\Models\HouseService;
use Modules\RestAPI\Entities\EstimateSection;
use App\Models\EstimateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RestAPI\Http\Requests\Estimate\CreateRequest;
use Modules\RestAPI\Http\Requests\Estimate\DeleteRequest;
use Modules\RestAPI\Http\Requests\Estimate\IndexRequest;
use Modules\RestAPI\Http\Requests\Estimate\ShowRequest;
use Modules\RestAPI\Http\Requests\Estimate\UpdateRequest;
use Carbon\Carbon;
use Modules\RestAPI\Entities\User;
use Modules\RestAPI\Entities\Product;
use App\Models\Tax;
use App\Models\TaxAccountNumber;
use App\Models\VatTypes;
use App\Models\VatPercentage;
use Modules\RestAPI\Entities\EstimateEmail;
use Validator;

class EstimateController extends ApiBaseController
{

    // Estimate List
    public function allMyEstimates(Request $request){
            $user = auth()->user()->user;
            $query = Estimate::join('users', 'users.id', '=', 'estimates.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'estimates.currency_id')->where('estimates.company_id',$user->company_id);
            if(!empty($request->client_name)){
                $query->where('users.name', 'LIKE', '%'.$request->client_name.'%');
            }
            if(!empty($request->number)){
                $query->where('estimates.estimate_number', $request->number);
            }
            if(!empty($request->status)){
                $query->where('estimates.status', $request->status);
            }
            if(!empty($request->archive)){
                $query->where('estimates.is_archived', $request->archive);
            }
            else{
                $query->where('estimates.is_archived', 0);
            }
            $estimates = $query->select('estimates.id','users.name','estimates.estimate_number','estimates.valid_till','estimates.note','estimates.total','estimates.status','estimates.outstanding','estimates.is_archived','estimates.estimate_type','currencies.currency_symbol')->orderBy('estimates.id','desc')->paginate($request->per_page);
            $data = array(
                "status" => true,
                'estimates' => $estimates
                
            );
            return Response()->json($data, $this->successStatus);
    }
    public function recentEstimates(Request $request){
        $user = auth()->user()->user;
        $query = Estimate::join('users', 'users.id', '=', 'estimates.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'estimates.currency_id')->where('estimates.company_id',$user->company_id);
        if(!empty($request->status)){
            $query->where('estimates.status', $request->status);
        }
        if(!empty($request->archive)){
            $query->where('estimates.is_archived', $request->archive);
        }
        else{
            $query->where('estimates.is_archived', 0);
        }
        $estimates = $query->select('estimates.id','users.name','estimates.estimate_number','estimates.valid_till','estimates.note','estimates.total','estimates.status','estimates.outstanding','estimates.is_archived','estimates.estimate_type','currencies.currency_symbol')->orderBy('estimates.id','desc')->take(5)->get();
        $data = array(
            "status" => true,
            'estimates' => $estimates
            
        );
        return Response()->json($data, $this->successStatus);
}

    public function allMyEstimatesById(Request $request,$id){
        $user = auth()->user()->user;
        $query = Estimate::join('users', 'users.id', '=', 'estimates.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'estimates.currency_id')->where('estimates.company_id',$user->company_id);
        if(!empty($request->search)){
            $query->where('users.name', 'LIKE', '%'.$request->search.'%')
            ->orWhere('estimates.estimate_number','LIKE', '%'.$request->search.'%')
            ->orWhere('estimates.status', 'LIKE','%'.$request->search.'%');
        }
        else{
            $query->where('estimates.is_archived', 0);
        }
        $estimates = $query->select('estimates.id','users.name','estimates.estimate_number','estimates.valid_till','estimates.note','estimates.total','estimates.status','estimates.outstanding','estimates.is_archived','estimates.estimate_type','currencies.currency_symbol')->orderBy('estimates.id','desc')->where('estimates.client_id',$id)->paginate($request->per_page);
        $data = array(
            "status" => true,
            'estimates' => $estimates
            
        );
        return Response()->json($data, $this->successStatus);
}
    

    public function createEstimateProposal(Request $request){
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
            $products = Product::select('id','name','name as text','price','taxes','account_code','in_stock')->where('company_id',$user->company_id)->get();
            // if($products){
            //     foreach($products as $key=>$product){
            //         $products[$key]->text = $product->name;
            //     }
            // }

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

            if($request->createType == 'proposal'){
                $lastNumber = Estimate::lastEstimateProposalNumber($user->company_id, 'proposal') + 1;
            }
            else{
                $lastNumber = Estimate::lastEstimateProposalNumber($user->company_id, 'estimate') + 1;
            }
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

    // Save estimate & Proposal Save it
    public function saveEstimateProposal(CreateRequest $request){
        try{

            $user = auth()->user()->user;
            // $estimateValid = Estimate::where('company_id',$user->company_id)->where('estimate_type',$request->estimate_type)->where('estimate_number',$request->estimate_number)->first();
            // if($estimateValid){
            //     $data = array(
            //         "status" => false,
            //         'message' => "Estimate number is already exist.",
                    
            //     );
            //     return Response()->json($data, $this->successStatus); 
            // }
            DB::beginTransaction();
            if($request->estimate_type == 'proposal'){
                $lastNumber = Estimate::lastEstimateProposalNumber($user->company_id, 'proposal') + 1;
            }
            else{
                $lastNumber = Estimate::lastEstimateProposalNumber($user->company_id, 'estimate') + 1;
            }
            $estimate = new Estimate();
            $estimate->company_id = $user->company_id;
            $estimate->client_id = $request->client_id;
            $estimate->date_of_issue = $request->date_of_issue;
            $estimate->valid_till = $request->due_date;
            $estimate->house_services_id = $request->house_service_id;
            $estimate->vat_type_id = $request->vat_type_id; 
            $estimate->sub_total = round($request->sub_total, 2);
            $estimate->total = round($request->total_amount, 2);
            $estimate->outstanding = round($request->total_amount, 2);
            $estimate->currency_id = $request->currency_id;
            $estimate->locale = $request->language_id;
            $estimate->e_sign = $request->e_sign;
            $estimate->note = trim_editor($request->note);
            $estimate->discount = round($request->discount, 2);
            $estimate->house_tax_total = round($request->house_tax_total, 2);
            $estimate->discount_type = 'percent';
            $estimate->discount_amount = round($request->total_discount, 2);
            $estimate->estimate_type = $request->estimate_type;
            $estimate->send_status = $request->send_status ? $request->send_status : 0;
            $estimate->status = $request->status;
            $estimate->description = trim_editor($request->term_condition);
            $estimate->estimate_number = $lastNumber;
            $estimate->from_company_name = $request->from_company_name;
            $estimate->from_address1 = $request->from_address1;
            $estimate->from_address2 = $request->from_address2;
            $estimate->from_phone = $request->from_phone;
            $estimate->to_client_name = $request->to_client_name;
            $estimate->to_address1 = $request->to_address1;
            $estimate->to_address2 = $request->to_address2;
            $estimate->to_phone = $request->to_phone;
            $estimate->save();

            if(!empty($request->section_name)){
                if(count($request->section_name) == count($request->section_text)){
                    $sectionText = $request->section_text;
                   foreach($request->section_name as $key=>$section){
                        $estimateSection = new EstimateSection();
                        $estimateSection->estimate_id = $estimate->id;
                        $estimateSection->section_name = $section;
                        $estimateSection->section_text = $sectionText[$key];
                        $estimateSection->save();
                   }
                }
                else{
                    $data = array(
                        "status" => false,
                        'message' => "Something went worng in add section please check once again. Thanks",
                        
                    );
                    return Response()->json($data, $this->successStatus); 
                }
            }
            
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
                            $estimateItem = new EstimateItem();
                            $estimateItem->estimate_id = $estimate->id;
                            if (array_key_exists($idx, $houseWorkId))
                            {
                                $estimateItem->house_work_id = $houseWorkId[$idx];
                            }
                            if (array_key_exists($idx, $articleName))
                            {
                                $estimateItem->item_name = $articleName[$idx];
                            }
                            if (array_key_exists($idx, $allowHouseTax))
                            {
                                $estimateItem->house_work_tax_applicable = $allowHouseTax[$idx] == true ? 1 : 0;
                            }
                            
                            $estimateItem->quantity = $quanty;
                        
                            if (array_key_exists($idx, $rate))
                            {
                                $estimateItem->unit_price = $rate[$idx];
                            }
                            if (array_key_exists($idx, $amount))
                            {
                                $estimateItem->amount = $amount[$idx];
                            }
                            if (array_key_exists($idx, $tax_amount))
                            {
                                $estimateItem->tax_amount = $tax_amount[$idx];
                            }
                            if (array_key_exists($idx, $article_id))
                            {
                                $estimateItem->product_id = $article_id[$idx];
                            }
                            if (array_key_exists($idx, $vat))
                            {
                                $estimateItem->tax_id = $vat[$idx];
                            }
                            if (array_key_exists($idx, $accountCode))
                            {
                                $estimateItem->account_code_id = $accountCode[$idx];
                            }
                            if (array_key_exists($idx, $unit))
                            {
                                $estimateItem->unit = $unit[$idx];
                            }
                            $estimateItem->save();
                        }
                    }

                }
                
            }

            DB::commit();
            if($request->send_status == 1){
                if(!empty($request->sent_to)){
                    $estimateEmail = new EstimateEmail();
                    $estimateEmail->estimate_id = $estimate->id;
                    $estimateEmail->client_id = $request->client_id;
                    $estimateEmail->email = $request->sent_to;
                    $estimateEmail->subject = $request->subject;
                    $estimateEmail->message = $request->message;
                    $estimateEmail->save();
                    event(new NewEstimateEvent($estimate));
                }
            }

            if($request->estimate_type == 'estimate'){
                $data = array(
                    "status" => true,
                    'message' => 'Estimate has been created successfully',
                    
                );
            }
            else{
                $data = array(
                    "status" => true,
                    'message' => 'Proposal has been created successfully',
                    
                );
            }
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

    //Get Single Estimate & Proposal
    public function getEstimateProposal($id){
        try{
            $estimateExit = Estimate::where('id',$id)->first();
            if($estimateExit){
                $estimate = Estimate::with('clientEmail','estimateSection','houseService','vatTypes','items','items.product','items.houseWork','items.TaxInfo','items.accountCode')->findOrFail($id);
                $data = array(
                    "status" => true,
                    'estimate' => $estimate
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'estimate' => "No data found"
                    
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
                'message' => "Some error occurred when retrive the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // Update Estimate & proposals
    public function updateEstimateProposal(UpdateRequest $request, $id){
        try{
            $user = auth()->user()->user;
            $estimate = Estimate::where('company_id',$user->company_id)->findOrFail($id);
            if($estimate){
                $estimate->client_id = $request->client_id;
                $estimate->date_of_issue = $request->date_of_issue;
                $estimate->valid_till = $request->due_date;
                $estimate->house_services_id = $request->house_service_id;
                $estimate->vat_type_id = $request->vat_type_id; 
                $estimate->sub_total = round($request->sub_total, 2);
                $estimate->total = round($request->total_amount, 2);
                $estimate->outstanding = round($request->total_amount, 2);
                $estimate->currency_id = $request->currency_id;
                $estimate->locale = $request->language_id;
                $estimate->e_sign = $request->e_sign;
                $estimate->note = trim_editor($request->note);
                $estimate->discount = round($request->discount, 2);
                $estimate->house_tax_total = round($request->house_tax_total, 2);
                $estimate->discount_type = 'percent';
                $estimate->discount_amount = round($request->total_discount, 2);
                $estimate->send_status = $request->send_status ? $request->send_status : 0;
                $estimate->status = $request->status;
                $estimate->description = trim_editor($request->term_condition);
                $estimate->from_company_name = $request->from_company_name;
                $estimate->from_address1 = $request->from_address1;
                $estimate->from_address2 = $request->from_address2;
                $estimate->from_phone = $request->from_phone;
                $estimate->to_client_name = $request->to_client_name;
                $estimate->to_address1 = $request->to_address1;
                $estimate->to_address2 = $request->to_address2;
                $estimate->to_phone = $request->to_phone;
                $estimate->save();

                if(!empty($request->section_name)){
                    $sections = EstimateSection::where('estimate_id',$id)->delete();
                    if(count($request->section_name) == count($request->section_text)){
                        $sectionText = $request->section_text;
                        foreach($request->section_name as $key=>$section){
                                $estimateSection = new EstimateSection();
                                $estimateSection->estimate_id = $id;
                                $estimateSection->section_name = $section;
                                $estimateSection->section_text = $sectionText[$key];
                                $estimateSection->save();
                        }
                    }
                    else{
                        $data = array(
                            "status" => false,
                            'message' => "Something went worng in add section please check once again. Thanks",
                            
                        );
                        return Response()->json($data, $this->successStatus); 
                    }
                }
                else{
                    EstimateSection::where('estimate_id',$id)->delete();
                }
                if(!empty($request->quantity)){
                    $quantity = $request->quantity;
                    if(count($quantity) > 0 && $quantity[0] !=''){
                    $estimateItemDelete = EstimateItem::where('estimate_id',$id)->delete();
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
                            $estimateItem = new EstimateItem();
                            $estimateItem->estimate_id = $estimate->id;
                            if (array_key_exists($idx, $houseWorkId))
                            {
                                $estimateItem->house_work_id = $houseWorkId[$idx];
                            }
                            if (array_key_exists($idx, $articleName))
                            {
                                $estimateItem->item_name = $articleName[$idx];
                            }
                            if (array_key_exists($idx, $allowHouseTax))
                            {
                                $estimateItem->house_work_tax_applicable = $allowHouseTax[$idx] == true ? 1 : 0;
                            }
                        
                            $estimateItem->quantity = $quanty;
                            
                            if (array_key_exists($idx, $rate))
                            {
                                $estimateItem->unit_price = $rate[$idx];
                            }
                            if (array_key_exists($idx, $amount))
                            {
                                $estimateItem->amount = $amount[$idx];
                            }
                            if (array_key_exists($idx, $tax_amount))
                            {
                                $estimateItem->tax_amount = $tax_amount[$idx];
                            }
                            
                            if (array_key_exists($idx, $article_id))
                            {
                                $estimateItem->product_id = $article_id[$idx];
                            }
                            if (array_key_exists($idx, $vat))
                            {
                                $estimateItem->tax_id = $vat[$idx];
                            }
                            if (array_key_exists($idx, $accountCode))
                            {
                                $estimateItem->account_code_id = $accountCode[$idx];
                            }
                            if (array_key_exists($idx, $unit))
                            {
                                $estimateItem->unit = $unit[$idx];
                            }
                            $estimateItem->save();
                        }
                    }

                    }
                }
                else{
                    EstimateItem::where('estimate_id',$id)->delete();
                }

                if($request->send_status == 1){
                    if(!empty($request->sent_to)){
                        $emailEstimate = EstimateEmail::where('estimate_id', $estimate->id)->first();
                        if($emailEstimate){
                            $emailEstimate->email = $request->sent_to;
                            $emailEstimate->subject = $request->subject;
                            $emailEstimate->message = $request->message;
                            $emailEstimate->save();
                        }
                        else{
                            $estimateEmail = new EstimateEmail();
                            $estimateEmail->estimate_id = $estimate->id;
                            $estimateEmail->client_id = $request->client_id;
                            $estimateEmail->email = $request->sent_to;
                            $estimateEmail->subject = $request->subject;
                            $estimateEmail->message = $request->message;
                            $estimateEmail->save();
                        }
                        event(new NewEstimateEvent($estimate));
                    }
                }

                if($estimate->estimate_type == 'estimate'){
                    $data = array(
                        "status" => true,
                        'message' => 'Estimate has been updated successfully',
                        
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'Proposal has been updated successfully',
                        
                    );
                }
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

    public function sendEstimate($id)
    {
        app()->make($this->indexRequest);

        $estimate = \App\Models\Estimate::findOrFail($id);
        event(new NewEstimateEvent($estimate));

        $estimate->send_status = 1;

        if ($estimate->status == 'draft') {
            $estimate->status = 'waiting';
        }

        $estimate->save();

        return ApiResponse::make(__('messages.estimateSentSuccessfully'));
    }

    public function getHouseServiceWork(){
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
        $data = array(
            "status" => true,
            'houseServices' => $houseServices,
            
        );
        return Response()->json($data, $this->successStatus);
    }

    // Change Estimate/Proposal Status
    public function updateStatusEstimateProposal(Request $request, $id){
        $user = auth()->user()->user;
        if(isset($request->status) && !empty($request->status)){
            $validator = Validator::make($request->all(), [ 
                'status' => 'required|in:declined,accepted,waiting,sent,draft,canceled',
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
       $estimate = Estimate::where('company_id',$user->company_id)->findOrFail($id);
       if($estimate){
            if(isset($request->status) && !empty($request->status)){
                $estimate->status = $request->status;
                $estimate->save();
                
                $data = array(
                    "status" => true,
                    'message' => 'The status has been changed successfully. Thanks'                
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $estimate->is_archived = $request->archive;
                $estimate->save();
                if($request->archive == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'The estimate/proposal has been archived successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'The estimate/proposal has been removed from arcived successfully. Thanks'                
                    );
                }
                return Response()->json($data, $this->successStatus);
            }

        }
        else{
            $data = array(
                "status" => false,
                'message' => "Estimate/Proposal not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }
    }

    public function deleteEstimatesProposals(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                Estimate::where('company_id',$user->company_id)->findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'The estimate/porposal has been deleted successfully'                
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
}
