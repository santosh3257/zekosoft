<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Http\Controllers\BaseController;
use Modules\RestAPI\Entities\Vendor;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;
use Modules\RestAPI\Http\Requests\Vendor\CreateVendorRequest;
use Modules\RestAPI\Http\Requests\Vendor\UpdateVendorRequest;
use Modules\RestAPI\Http\Requests\Vendor\DeleteVendorRequest;
use Validator;
use App\Imports\ImportVendor;
use App\Traits\ImportExcel;
use App\Exports\ExportVendors;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rule;

class VendorController extends ApiBaseController {
    use ImportExcel;
    protected $model = '';
    //Get All vendors
    public function allVendors(Request $request){
        $user = auth()->user()->user;
        $query = Vendor::where('company_id',$user->company_id);
        if(!empty($request->status)){
            $query->where('status', 'LIKE', $request->status);
        }
        if(!empty($request->name)){
            $query->where('name', 'LIKE', $request->name);
        }
        if(!empty($request->ssn)){
            $query->where('ssn', 'LIKE', '%'.$request->ssn.'%');
        }
        if($request->archive == 1){
            $query->where('archive', 1);
        }
        else{
            $query->where('archive', 0);
        }
        $vendors = $query->paginate($request->per_page);
        $data = array(
            "status" => true,
            'projects' => $vendors
            
        );
        return Response()->json($data, $this->successStatus);
    }
    // Add New Vendor
    public function addMyVendor(Request $request){
        try{
            
            DB::beginTransaction();
            $user = auth()->user()->user;
            $com_id = $user->company_id;
            $usr_email = $request->email;
            $validator = Validator::make($request->all(), [ 
                'name' => 'required',
                'ssn' => 'required',
                'vat_number' => 'required',
                'email' => [
                    'required',
                     Rule::unique('vendors')->where(function ($query) use($com_id,$usr_email) {
                       return $query->where('company_id', $com_id)->where('email', $usr_email);
                     }),
                ],
                "bankgiro" => 'required',
                "bank_fee" => 'required|in:sender,receiver,both',
                "billing_address" => 'required',
                'country' => "required",
                'state' => 'required',
                'city' => 'required',
                'postal_code' => 'required',
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $insertVendor = new Vendor();
            $insertVendor->company_id = $user->company_id;
            $insertVendor->added_by = $user->id;
            $insertVendor->name = $request->name;
            $insertVendor->ssn = $request->ssn;
            $insertVendor->vat_number = $request->vat_number;
            $insertVendor->email = $request->email;
            $insertVendor->phone_number = $request->phone_number;
            $insertVendor->bankgiro = $request->bankgiro;
            $insertVendor->plusgiro = $request->plusgiro;
            $insertVendor->iban = $request->iban;
            $insertVendor->bic = $request->bic;
            $insertVendor->bank = $request->bank;
            $insertVendor->clearing_num = $request->clearing_num;
            $insertVendor->account_num = $request->account_num;
            $insertVendor->bank_fee = $request->bank_fee;
            $insertVendor->billing_address = $request->billing_address;
            $insertVendor->country = $request->country;
            $insertVendor->state = $request->state;
            $insertVendor->city = $request->city;
            $insertVendor->postal_code = $request->postal_code;
            $insertVendor->status = 'active';
            $insertVendor->note = $request->note;
            $insertVendor->locale = $request->locale;
            $insertVendor->currency_id = $request->currency_id;
            $insertVendor->tax_id = $request->tax_id;
            $insertVendor->setting_language = $request->locale;
            $insertVendor->setting_currency = $request->currency_id;
            $insertVendor->setting_vat = $request->setting_vat;
            $insertVendor->setting_tax_code = $request->setting_tax_code;
            $insertVendor->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Vendor added successfully',
                
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

    // Edit Vendor
    public function EditMyVendor($id){
        $user = auth()->user()->user;
        $vendor = Vendor::where('company_id',$user->company_id)->where('id',$id)->first();
        if($vendor){

        $data = array(
            "status" => true,
            'vendor' => $vendor
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Vendor not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }
    }
    // View Vendor
    public function ViewMyVendor($id){
        $user = auth()->user()->user;
        $vendor = Vendor::where('company_id',$user->company_id)->where('id',$id)->first();
        if($vendor){

        $data = array(
            "status" => true,
            'vendor' => $vendor
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Vendor not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }
    }
    // Update Vendor
    public function UpateMyVendor(Request $request, $id){
        try{
            $validator = Validator::make($request->all(), [ 
                'name' => 'required',
                'ssn' => 'required',
                'vat_number' => 'required',
                'email' => 'required|email',
                "bankgiro" => 'required',
                "bank_fee" => 'required|in:sender,receiver,both',
                "billing_address" => 'required',
                'country' => "required",
                'state' => 'required',
                'city' => 'required',
                'postal_code' => 'required',
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $vendor = Vendor::findOrFail($id);
            if($vendor){

                $vendor->name = $request->name;
                $vendor->ssn = $request->ssn;
                $vendor->vat_number = $request->vat_number;
                $vendor->email = $request->email;
                $vendor->phone_number = $request->phone_number;
                $vendor->bankgiro = $request->bankgiro;
                $vendor->plusgiro = $request->plusgiro;
                $vendor->iban = $request->iban;
                $vendor->bic = $request->bic;
                $vendor->bank = $request->bank;
                $vendor->clearing_num = $request->clearing_num;
                $vendor->account_num = $request->account_num;
                $vendor->bank_fee = $request->bank_fee;
                $vendor->billing_address = $request->billing_address;
                $vendor->country = $request->country;
                $vendor->state = $request->state;
                $vendor->city = $request->city;
                $vendor->postal_code = $request->postal_code;
                $vendor->note = $request->note;
                $vendor->locale = $request->locale;
                $vendor->currency_id = $request->currency_id;
                $vendor->tax_id = $request->tax_id;
                $vendor->setting_language = $request->locale;
                $vendor->setting_currency = $request->currency_id;
                $vendor->setting_vat = $request->setting_vat;
                $vendor->setting_tax_code = $request->setting_tax_code;
                $vendor->save();

                $data = array(
                    "status" => true,
                    'message' => 'Vendor update successfully'
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Vendor not found'
                    
                );
                return Response()->json($data, $this->forbiddenStatus);
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
                'message' => "Some error occurred when updating the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // Delete Vendor
    public function DeleteMyVendor(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                Vendor::where('company_id',$user->company_id)->findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'The vendor has been deleted successfully'                
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

    //function for change status of vendor like Archive 0 or 1 and status 'Active','Inactive'

    public function updateStatusVendor(Request $request, $id){
        $user = auth()->user()->user;
        if(isset($request->status) && !empty($request->status)){
            $validator = Validator::make($request->all(), [ 
                'status' => 'required|in:active,inactive',
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
       $vendor = Vendor::where('company_id',$user->company_id)->findOrFail($id);
       if($vendor){
            if(isset($request->status) && !empty($request->status)){
                $vendor->status = $request->status;
                $vendor->save();
                
                $data = array(
                    "status" => true,
                    'message' => 'The status has been changed successfully. Thanks'                
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $vendor->archive = $request->archive;
                $vendor->save();
                if($request->archive == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'The vendor has been archived successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'The vendor has been removed from arcived successfully. Thanks'                
                    );
                }
                return Response()->json($data, $this->successStatus);
            }

        }
        else{
            $data = array(
                "status" => false,
                'message' => "Vendor not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }

    }

    //function for import vendors
     
    public function createVendorsFile(Request $request){
        try{
            $validator = Validator::make($request->all(), [ 
                'import_file' => 'required|file|mimes:xls,xlsx,csv,txt'
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            DB::beginTransaction();
            $user = auth()->user()->user;
            $columsName = $this->importFileJobProcess($request, ImportVendor::class);
            if(!empty($columsName)){
                $columns = $columsName[0];
                $requiredColumns = array('Name','Ssn','Vat Number','Email','Bankgiro','Bank Fee','Billing Address','Country','State','City','Postal Code');
                foreach($requiredColumns as $column){
                    if (!in_array($column, $columns)){
                        return response()->json(['message'=>$column." doesn't exist in file",'status' => false], $this->successStatus);
                    }
                }
                array_splice($columsName, 0,1);
                //return $columsName;
                $columnArray = [];
                if($columsName){
                    foreach($columsName as $index=>$values){
                        if(!empty($values)){
                            $dataArray = [];
                            foreach($values as $key=>$value){
                                $column_name = str_replace(' ','_',strtolower($columns[$key]));
                                $dataArray[$column_name] =  $value;
                            }
                        $columnArray[$index] = $dataArray;
                        }
                        
                    }
                }

                if(count($columnArray) > 0){
                    //return Response()->json($columnArray, $this->successStatus);
                    foreach($columnArray as $idx=>$column_value){
                        $findEmail = Vendor::where('email',$column_value['email'])->where('added_by',$user->id)->first();
                        if (empty($findEmail)) {
                        $vendor = new Vendor();
                        $vendor->company_id = $user->company_id;
                        $vendor->added_by = $user->id;
                        $vendor->name = $column_value['name'];
                        $vendor->ssn = $column_value['ssn'];
                        $vendor->vat_number = $column_value['vat_number'];
                        $vendor->email = $column_value['email'];
                        $vendor->bankgiro = $column_value['bankgiro'];
                        $vendor->bank_fee = $column_value['bank_fee'];
                        $vendor->billing_address = $column_value['billing_address'];
                        $vendor->country = $column_value['country'];
                        $vendor->state = $column_value['state'];
                        $vendor->city = $column_value['city'];
                        $vendor->postal_code = $column_value['postal_code'];
                        $vendor->save();
                        }
                    }

                    DB::commit();
                    $data = array(
                        "status" => true,
                        'message' => 'The file has been imported successfully. Thanks'                
                    );
                    return Response()->json($data, $this->successStatus); 
                }
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "No data found in your file",
                    
                );
                return Response()->json($data, $this->successStatus);
            }

            

        }catch (ApiException $e) {
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }

    }
    // Download Article file
    public function getVendorsFile(){
        $user = auth()->user()->user;
        $fileName = 'download/myvendors-'.$user->company_id.'.csv';
        Excel::store(new ExportVendors, $fileName, 'public');
        $url = url('storage/'.$fileName);
        $data = array(
            "status" => true,
            'url' => $url
            
        );
        return Response()->json($data, $this->successStatus); 
    }


}
