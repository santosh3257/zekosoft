<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Helper\Files;
use App\Models\ClientNote;
use App\Scopes\ActiveScope;
use App\Models\Notification;
use App\Models\ContractType;
use App\Models\UserAuth;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\ClientCategory;
use App\Models\LanguageSetting;
use App\Models\UniversalSearch;
use App\Models\ClientSubCategory;
use App\Models\PurposeConsentUser;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use Validator;
use App\Events\NewUserEvent;
use App\Imports\ClientImport;
use App\Exports\ClientsExport;
use App\Jobs\ImportClientJob;
use App\Traits\ImportExcel;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\Admin\Employee\ImportRequest;
use Storage;
use Modules\RestAPI\Http\Requests\Client\DeleteRequest;
use Modules\RestAPI\Entities\ClientCoApplicant;

class MyClientController extends ApiBaseController {

    use ImportExcel;
    protected $model = '';

    // Get My All Clients
    public function getMyClients(Request $request){
        $user = auth()->user()->user;
        //$clients = User::with('clientDetails')->where('company');
        $clients = User::allMyClientsWithPaginate($request->client_name,$request->account_type,$request->co_applicant,$request->status,$request->per_page);
        $data = array(
            "status" => true,
            'clients' => $clients
            
        );
        return Response()->json($data, $this->successStatus);
    }

    // Create/add client 
    public function createMyClient(Request $request){
        try{
            DB::beginTransaction();
            $auth_id = auth()->user()->id;
            $currentUser = User::where('user_auth_id',$auth_id)->first();
            $company = company();
            $client = User::allMyClients();
            if (!is_null($company) && ($company->package->employee_unlimited == 'false') && $client->count() >= $company->package->max_employees) {
                $data = array(
                    "status" => false,
                    'message' => __('superadmin.maxClientsLimitReached'),
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            if($request->account_type == 'individual'){
                $validator = Validator::make($request->all(), [ 
                    'email' => 'required|email',
                    'name' => 'required',
                    'phone_number' => 'required|numeric',
                    'social_security' => 'required',
                    'billing_address' => 'required',
                    'postal_code' => 'required',
                    'city' => 'required',
                    'account_type' => 'required|in:individual,organization',
                ]);
            }
            else{
                $validator = Validator::make($request->all(), [ 
                    'email' => 'required|email',
                    'company_name' => 'required',
                    'origanisation_number' => 'required',
                    'account_type' => 'required|in:individual,organization',
                    'vat_number' => 'required',
                    'billing_address' => 'required',
                    'city' => 'required',
                    'postal_code' => 'required',
                ]);
            }
            if ($validator->fails()) { 
                return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
           
            
           
            if ($request->email != '') {
                $userAuth = UserAuth::createUserAuthCredentials($request->email);
                $data['user_auth_id'] = $userAuth->id;
                //event(new NewUserEvent($userAuth, session('auth_pass')));
            }
            
            $checkEmailExitSameCompany = User::where('email',$request->email)->where('company_id',$currentUser->company_id)->first();
            if($checkEmailExitSameCompany){
                $data = array(
                    "status" => false,
                    'message' => 'The email has already been taken',
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            $data['country_id'] = $request->country;
            if(!empty($request->name)){
                $data['name'] = $request->name;
            }
            else{
                $data['name'] = $request->company_name;
            }
            $data['email'] = $request->email;
            $data['mobile'] = $request->phone_number;
            $data['email_notifications'] = 1;
            $data['gender'] = $request->gender ?? null;
            $data['locale'] = $request->locale ?? 'en';
            $data['country_phonecode'] = $request->country_code;
    
            if ($request->has('telegram_user_id')) {
                $data['telegram_user_id'] = $request->telegram_user_id;
            }
    
            if ($request->hasFile('image')) {
                $data['image'] = Files::uploadLocalOrS3($request->image, 'avatar', 300);
            }
    
            if ($request->hasFile('company_logo')) {
                $data['company_logo'] = Files::uploadLocalOrS3($request->company_logo, 'client-logo', 300);
            }
            
            $user = User::create($data);
            $user->clientDetails()->create($data);
    
            $client_id = $user->id;
            $client = $user->clientDetails;
            $client->company_name = $request->company_name;
            $client->social_security = $request->social_security;
            $client->account_type = $request->account_type;
            $client->property_name = $request->property_name;
            $client->origanisation_number = $request->origanisation_number;
            $client->vat_number = $request->vat_number;
            $client->address = $request->address;
            $client->shipping_address = $request->billing_address;
            $client->postal_code = $request->postal_code;
            $client->state = $request->state;
            $client->country_id = $request->country;
            $client->city = $request->city;
            $client->note = $request->note;
            $client->mobile = $request->phone_number;
            $client->payment_reminder = $request->payment_reminder;
            $client->reminder_days = $request->reminder_days;
            $client->reminder_due = $request->reminder_due;
            $client->charge_type = $request->charge_type;
            $client->late_fee_charge = $request->late_fee_charge;
            $client->charge_days = $request->charge_days;
            $client->currency = $request->currency;
            $client->save();
            //add client co-applicant
            if($request->account_type == 'individual'){
                if(!empty($request->co_applicant_name)){
                $applicant_name = $request->co_applicant_name;
                    if(count($applicant_name) > 0 && $applicant_name[0] !=''){
                    $co_applicant_name = $request->co_applicant_name;
                    $co_applicant_social_security_no = $request->co_applicant_social_security_no;
                        foreach($co_applicant_name as $idx=>$applicantData){
                            if($applicantData !='' && $applicantData !=null){
                                $co_applicantItem = new ClientCoApplicant();
                                $co_applicantItem->client_id = $user->id;
                                $co_applicantItem->company_id = $user->company_id;
                                if (array_key_exists($idx, $co_applicant_name))
                                {
                                    $co_applicantItem->co_applicant_name = $co_applicant_name[$idx];
                                }
                                if (array_key_exists($idx, $co_applicant_social_security_no))
                                {
                                    $co_applicantItem->co_applicant_social_security_no = $co_applicant_social_security_no[$idx];
                                }
                                $co_applicantItem->save();
                                } 
                            }
                         } 
                        }
                    }



            // To add custom fields data
            // if ($request->custom_fields_data) {
            //     $client = $user->clientDetails;
            //     $client->updateCustomFieldData($request->custom_fields_data);
            // }
    
            $role = Role::where('name', 'client')->select('id')->first();
            $user->attachRole($role->id);
            $user->insertUserRolePermission($role->id);
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'The client added successfully. Thanks'                
            );
            return Response()->json($data, $this->successStatus);            
        
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
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // Edit Single Client
    public function editMyClient($client_id){
        $user = auth()->user()->user;
        $client = User::withoutGlobalScope(ActiveScope::class)->with('clientDetails','clientDetails.country')->findOrFail($client_id);
        $clientCoapplicant = ClientCoApplicant::where('client_id',$client_id)->where('company_id',$user->company_id)->get();
        $data = array(
            "status" => true,
            'clients' => $client,
            "clientCoApplicant"=>$clientCoapplicant
            
        );
        return Response()->json($data, $this->successStatus);
    }

    // Update Client Info
    public function updateMyClient(Request $request, $client_id){
        try{
            if($request->account_type == 'individual'){
                $validator = Validator::make($request->all(), [ 
                    'email' => 'required|email',
                    'name' => 'required',
                    'phone_number' => 'required|numeric',
                    'social_security' => 'required',
                    'billing_address' => 'required',
                    'postal_code' => 'required',
                    'city' => 'required',
                    'account_type' => 'required|in:individual,organization',
                ]);
            }
            else{
                $validator = Validator::make($request->all(), [ 
                    'email' => 'required|email',
                    'company_name' => 'required',
                    'origanisation_number' => 'required',
                    'account_type' => 'required|in:individual,organization',
                    'vat_number' => 'required',
                    'billing_address' => 'required',
                    'city' => 'required',
                    'postal_code' => 'required',
                ]);
            }
            if ($validator->fails()) { 
                return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $user = User::withoutGlobalScope(ActiveScope::class)->with('clientDetails','clientDetails.country')->findOrFail($client_id);
            if(!empty($request->name)){
                $user->name = $request->name;
            }
            else{
                $user->name = $request->company_name;
            }
            //return 'check';
            $user->name = $request->name;
            $user->country_phonecode = $request->country_code;
            $user->mobile = $request->phone_number;
            $user->country_id = $request->country;
            $user->locale = $request->locale ?? 'en';
            $user->save();
            $client = $user->clientDetails;
            $client->company_name = $request->company_name;
            $client->social_security = $request->social_security;
            $client->account_type = $request->account_type;
            $client->property_name = $request->property_name;
            $client->origanisation_number = $request->origanisation_number;
            $client->vat_number = $request->vat_number;
            $client->address = $request->address;
            $client->shipping_address = $request->billing_address;
            $client->postal_code = $request->postal_code;
            $client->state = $request->state;
            $client->country_id = $request->country;
            $client->city = $request->city;
            $client->note = $request->note;
            $client->mobile = $request->phone_number;
            $client->payment_reminder = $request->payment_reminder;
            $client->reminder_days = $request->reminder_days;
            $client->reminder_due = $request->reminder_due;
            $client->charge_type = $request->charge_type;
            $client->late_fee_charge = $request->late_fee_charge;
            $client->charge_days = $request->charge_days;
            $client->currency = $request->currency;
            $client->save();

             //add client co-applicant
             if($request->account_type == 'individual'){
                if(!empty($request->co_applicant_name)){
                $applicant_name = $request->co_applicant_name;
                    if(count($applicant_name) > 0 && $applicant_name[0] !=''){
                    ClientCoApplicant::where('client_id',$client_id)->delete();
                    $co_applicant_name = $request->co_applicant_name;
                    $co_applicant_social_security_no = $request->co_applicant_social_security_no;
                        foreach($co_applicant_name as $idx=>$applicantData){
                            if($applicantData !='' && $applicantData !=null){
                                $co_applicantItem = new ClientCoApplicant();
                                $co_applicantItem->client_id = $user->id;
                                $co_applicantItem->company_id = 80;
                                if (array_key_exists($idx, $co_applicant_name))
                                {
                                    $co_applicantItem->co_applicant_name = $co_applicant_name[$idx];
                                }
                                if (array_key_exists($idx, $co_applicant_social_security_no))
                                {
                                    $co_applicantItem->co_applicant_social_security_no = $co_applicant_social_security_no[$idx];
                                }
                                $co_applicantItem->save();
                            }
                        }
                    }
                 }
            }

            $data = array(
                "status" => true,
                'message' => 'The client updated successfully. Thanks',
                
            );
            return Response()->json($data, $this->successStatus);  

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
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // Create Client via CSV file
    public function addClientsCSV(ImportRequest $request){
        try{
            DB::beginTransaction();
            $auth_id = auth()->user()->id;
            $currentUser = User::where('user_auth_id',$auth_id)->first();
            $columsName = $this->importFileJobProcess($request, ClientImport::class);
            if(!empty($columsName)){
                    $columns = $columsName[0];
                    $search = array('*');
                    $columns = str_replace($search, "", $columns);
                    $requiredColumns = array('Name','Email','Social Security Number','Account Type','Address','City','State','Postal Code');
                    foreach($requiredColumns as $column){
                        if (!in_array($column, $columns)){
                            return response()->json(['errors'=>$column." doesn't exist in file",'status' => false], $this->successStatus);
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
                    //return $columnArray;
                    if(count($columnArray) > 0){
                        //check csv column fields value is empty or not
                        $requiredColumns = array('name','email','social_security_number','account_type','address','city','state','postal_code');
                        foreach($columnArray as $key=>$columnValue){
                            $keyName = array_keys($columnValue);
                            foreach($requiredColumns as $idx=>$keyNameVal){
                                if (in_array($keyNameVal, $keyName)){
                                    $result = $columnValue[$keyNameVal];
                                    if(!$result){
                                        return response()->json(['status' => false,'errors'=>$keyNameVal." field is empty in your file, Please check your file."], $this->successStatus);
                                    }
                                }
                            }
                            //return 'check';
                        }
                        //end code for check csv column fields value is empty or not
                        foreach($columnArray as $idx=>$columnValue){
                            $checkEmailExitSameCompany = User::where('email',$columnValue['email'])->where('company_id',$currentUser->company_id)->first();
                            if($checkEmailExitSameCompany){
                                $data = array(
                                    "status" => false,
                                    'message' => $columnValue['email'].' email has already been taken',
                                    
                                );
                                return Response()->json($data, $this->successStatus);
                            }
                        }

                        // Create Client
                        foreach($columnArray as $idx=>$column_value){
                            $userAuth = UserAuth::createUserAuthCredentials($column_value['email']);
                            $data['user_auth_id'] = $userAuth->id;

                            $data['name'] = $column_value['name'];
                            $data['email'] = $column_value['email'];
                            $data['mobile'] = $column_value['mobile'];
                            $data['email_notifications'] = 1;
                            $data['gender'] = $column_value['gender'] ?? null;
                            $data['locale'] = $column_value['locale'] ?? 'en';
                    
                            
                            $user = User::create($data);
                            $user->clientDetails()->create($data);
                    
                            $client_id = $user->id;
                            $client = $user->clientDetails;
                            if(isset($column_value['company_name'])){
                            $client->company_name = $column_value['company_name'];
                            }
                            $client->social_security = $column_value['social_security_number'];
                            $client->account_type = $column_value['account_type'];
                            if(isset($column_value['applicant_name'])){
                                $client->co_applicant_name = $column_value['applicant_name'];
                            }
                            if(isset($column_value['applicant_social_security_number'])){
                            $client->co_applicant_social_security_number = $column_value['applicant_social_security_number'];
                            }
                            if(isset($column_value['property_name'])){
                            $client->property_name = $column_value['property_name'];
                            }
                            if(isset($column_value['organisation_number'])){
                            $client->origanisation_number = $column_value['organisation_number'];
                            }
                            if(isset($column_value['vat_number'])){
                            $client->vat_number = $column_value['vat_number'];
                            }
                            if(isset($column_value['address'])){
                            $client->address = $column_value['address'];
                            }
                            if(isset($column_value['billing_address'])){
                            $client->shipping_address = $column_value['billing_address'];
                            }
                            if(isset($column_value['postal_code'])){
                            $client->postal_code = $column_value['postal_code'];
                            }
                            if(isset($column_value['state'])){
                            $client->state = $column_value['state'];
                            }
                            if(isset($column_value['city'])){
                            $client->city = $column_value['city'];
                            }
                            if(isset($column_value['note'])){
                            $client->note = $column_value['note'];
                            }
                            if(isset($column_value['mobile'])){
                            $client->mobile = $column_value['mobile'];
                            }
                            $client->save();
                    
                            $role = Role::where('name', 'client')->select('id')->first();
                            $user->attachRole($role->id);
                            $user->insertUserRolePermission($role->id);
                            
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

    public function getClientsFile(){
        //return $url = url('/login');
        $user = auth()->user()->user;
        $fileName = 'download/myclient-'.$user->company_id.'.csv';
        Excel::store(new ClientsExport, $fileName, 'public');
        $url = url('storage/'.$fileName);
        $data = array(
            "status" => true,
            'url' => $url
            
        );
        return Response()->json($data, $this->successStatus); 
    }

    // Change Client Status
    public function changeClientStatus(Request $request, $client_id){
        //return "sdfsdfds";
        $validator = Validator::make($request->all(), [ 
            'status' => 'required|in:active,deactive',
        ]);
    
        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        $client = User::withoutGlobalScope(ActiveScope::class)->findOrFail($client_id);
        if($client){
            $client->status = $request->status;
            $client->save();
            $data = array(
                "status" => true,
                'message' => 'The status has been changed successfully. Thanks'                
            );
            return Response()->json($data, $this->successStatus); 
            
        }
        else{
            $data = array(
                "status" => false,
                'message' => "Client not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }
    }

    // Delete My client
    public function deleteMyClient(Request $request){
        /* $client = User::withoutGlobalScope(ActiveScope::class)->findOrFail($client_id);
        if($client){
            $client->deleted_at = now();
            $client->save();
            $data = array(
                "status" => true,
                'message' => 'The client has been deleted successfully. Thanks'                
            );
            return Response()->json($data, $this->successStatus); 
            
        }
        else{
            $data = array(
                "status" => false,
                'message' => "Client not found",
                
            );
            return Response()->json($data, $this->successStatus);
        } */
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                $client = User::withoutGlobalScope(ActiveScope::class)->findOrFail($id);
                if($client){
                    $client->deleted_at = now();
                    $client->save();   
                }
            }

            $data = array(
                "status" => true,
                'message' => 'The client has been deleted successfully'                
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

    public function getClientCoApplicants(Request $request,$client_id){
        $user = auth()->user()->user;
        $getCoApplicant = ClientCoApplicant::where('client_id',$client_id)->where('company_id',$user->company_id)->get();
        if($getCoApplicant){
            $data = array(
                "status" => true,
                'client_co_applicant' => $getCoApplicant
                
            );
            return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'The ID is required to delete it or id not found!'              
            );
            
            return Response()->json($data, $this->successStatus);

        }
        return $getCoApplicant;


    }

}
