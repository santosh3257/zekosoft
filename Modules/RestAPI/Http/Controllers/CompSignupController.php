<?php

namespace Modules\RestAPI\Http\Controllers;

use Froiden\RestAPI\ApiController;
use App\Models\EmployeeDetails;
use App\Models\Role;
use App\Models\UniversalSearch;
use App\Models\User;
use App\Models\Country;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Permission;
use App\Scopes\ActiveScope;
use App\Scopes\CompanyScope;
use App\Models\SuperAdmin\GlobalCurrency;
use App\Models\PermissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Traits\CurrencyExchange;
use App\Models\SuperAdmin\Package;
use App\Models\CompanyAddress;
use Modules\RestAPI\Entities\UserAuth;
use Modules\RestAPI\Http\Requests\Signup\CompanySignupRequest;
use Modules\RestAPI\Http\Requests\Signup\ResendOTPRequest;
use Illuminate\Support\Facades\DB;
use Froiden\RestAPI\ApiResponse;
use Froiden\RestAPI\Exceptions\ApiException;
use Modules\RestAPI\Entities\UsersOtp; 
use Modules\RestAPI\Http\Requests\Signup\VerifyOTPRequest;
use Validator;
use App\Events\SignupOtpEvent;
use App\Events\ForgotPasswordOtpEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;


class CompSignupController extends ApiBaseController {

    protected $model = '';

    public function compSignup(CompanySignupRequest $request){
        try {
            DB::beginTransaction();

            $company = $this->storeAndUpdate(new Company(), $request);
            
            // To add custom fields data
            if ($request->custom_fields_data) {
                $company->updateCustomFieldData($request->custom_fields_data);
            }

            $otp = $this->generateOTP();
            $userId = $this->addUser($company, $request);

            $companyOTP = new UsersOtp();
            $companyOTP->user_id = $userId;
            $companyOTP->otp = $otp;
            $companyOTP->expireDate = now()->addMinute(10);
            $companyOTP->save();

            $userAuth = UserAuth::findOrFail($userId);
            event(new SignupOtpEvent($userAuth->user));
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'The Otp has been send in your mail account. please check it inbox or spam. Thanks',
                
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

    // Add & Update company info
    public function storeAndUpdate(Company $company, $request)
    {
        $company->company_name = $request->company_name;
        $company->app_name = $request->company_name;
        $company->company_email = $request->email;
        $company->company_phone = $request->company_phone;
        $company->website = $request->website;
        $company->address = $request->address;
        $company->timezone = $request->timezone;
        $company->locale = $request->locale;
        $company->package_id = 5;
        $company->currency_id = 1;
        $company->trial_ends_at = now()->addMonth();
        $company->licence_expire_on = now()->addMonth();
        $company->package_type = 'monthly';
        $company->subscription_updated_at = now();
        $company->status = 'inactive';

        if($request->has('approved')){
            $company->approved = $request->approved;
        }

        if ($request->hasFile('logo')) {
            $company->logo = Files::upload($request->logo, 'app-logo');
            $company->light_logo = $company->logo;
        }

        //$company->last_updated_by = $this->user->id;

        // if (module_enabled('Subdomain')) {
        //     $company->sub_domain = $request->sub_domain;
        // }

        $company->save();

        //$company->defaultAddress->update(['address' => $request->address]);

        return $company;
    }

    // Fun for add new user
    // Create New company with some role & permission 
    public function addUser($company, $request)
    {
        // Save Admin
        $user = User::withoutGlobalScopes([CompanyScope::class, ActiveScope::class])->where('company_id', $company->id)->where('email', $request->email)->first();

        if (is_null($user)) {
            $user = new User();
        }

        $userAuth = UserAuth::createUserAuthCredentials($request->email);

        $user->company_id = $company->id;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->status = 'active';
        $user->user_auth_id = $userAuth->id;
        $user->save();

        if ($request->password != '') {
            UserAuth::where('id', $user->user_auth_id)->update(['password' => bcrypt($request->password)]);
        }

        if (!$user->hasRole('admin')) {

            // Attach Admin Role
            $adminRole = Role::withoutGlobalScope(CompanyScope::class)->where('name', 'admin')->where('company_id', $company->id)->first();

            $employeeRole = Role::withoutGlobalScope(CompanyScope::class)->where('name', 'employee')->where('company_id', $user->company_id)->first();

            $user->roles()->attach($adminRole->id);
            $this->addEmployeeDetails($user, $employeeRole, $company->id);


            $allPermissions = Permission::orderBy('id')->get()->pluck('id')->toArray();
            $permissionType = PermissionType::where('name', 'all')->first();

            foreach ($allPermissions as $permission) {
                $user->permissionTypes()->attach([
                    $permission => [
                        'permission_type_id' => $permissionType->id ?? PermissionType::ALL
                    ]]);
            }
        }

        return $userAuth->id;
    }


    private function addEmployeeDetails($user, $employeeRole, $companyId)
    {
        $employee = new EmployeeDetails();
        $employee->user_id = $user->id;
        $employee->company_id = $companyId;
        /* @phpstan-ignore-line */
        $employee->employee_id = 'EMP-' . $user->id;
        /* @phpstan-ignore-line */
        $employee->save();

        $search = new UniversalSearch();
        $search->searchable_id = $user->id;
        $search->company_id = $companyId;
        $search->title = $user->name;
        $search->route_name = 'employees.show';
        $search->save();

        // Assign Role
        $user->roles()->attach($employeeRole->id);
        /* @phpstan-ignore-line */
    }

    /*
     * Generate OTP
     */ 
    private function generateOTP(){
        $otp = random_int(0, 999999);
        $otp = str_pad($otp, 6, 6, STR_PAD_LEFT);
        return $otp;
        //return 654321;
    }

    // Verify Otp
    public function verifyCompanyOtp(VerifyOTPRequest $request){
        try {
            $user = User::select('id','company_id','email','user_auth_id')->where('email',$request->email)->first();
            if($user){
                $userRoleName = $user->roles->first()->name;
                $file = File::get(public_path('user-uploads/roles-permissions/roles_permissions.json'));
                $fileData = json_decode($file, true);
                $permissionDatas = array_column($fileData, $userRoleName);
                $companyOtp = UsersOtp::where('user_id',$user->user_auth_id)->where('otp',$request->otp)->first();
                if($companyOtp){
                    $nowDateTime = date('Y-m-d H:i:s');
                    $expiry = now()->addYear();
                    
                    if($companyOtp->expireDate >= $nowDateTime){
                        $companyOtp->delete();

                        $days = 365;
                        $minutes = 60 * 60 * $days;
                        $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];
                        if((!empty($user->company_id)) && $request->verify === 'signup'){
                            $company = Company::select('id','company_name','company_email','company_phone','address','status','otp_verify','name','package_id','social_security','country_code','country','origination_number','vat_number','bankgiro','plusgiro','city','state','bic','step')->where('company_email',$request->email)->first();
                            $company->status = 'active';
                            $company->otp_verify = 'true';
                            $company->save();
                            // Auth::loginUsingId($user->user_auth_id);
                            // $currentUser = auth()->user();
                            // $tokenName = Str::slug($currentUser->name . ' ' . $currentUser->id);
                            // $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                            $data = array(
                                "status" => true,
                                'message' => 'OTP has been verified. Thanks',
                                'company' => $company,
                                "rolePermission"=>$permissionDatas
                                
                            );
                            return Response()->json($data, $this->successStatus);
                        }
                        else if($request->verify === 'reset'){
                            $passwordToken = str_random(60);
                            DB::table('password_resets')->where('email',$request->email)->delete();
                            DB::table('password_resets')->insert([
                                'email' => $request->email,
                                'token' => $passwordToken,
                                'created_at' => now()
                            ]);
                            $data = array(
                                "status" => true,
                                'message' => 'OTP has been verified. Thanks',
                                "password_token" => $passwordToken,
                                "rolePermission"=>$permissionDatas
                                
                            );

                            return Response()->json($data, $this->successStatus);
                        }
                        else{
                            Auth::loginUsingId($user->user_auth_id);
                            $currentUser = auth()->user();
                            $tokenName = Str::slug($currentUser->name . ' ' . $currentUser->id);
                            $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                            $data = array(
                                "status" => true,
                                'message' => 'OTP has been verified. Thanks',
                                'token' => $token,
                                "rolePermission"=>$permissionDatas
                                
                            );
                            return Response()->json($data, $this->successStatus);
                        }

                    }
                    else{
                        $data = array(
                            "status" => false,
                            'message' => "OTP is expired"
                            
                        );
                        return Response()->json($data, $this->successStatus); 
                    }
                }
                else{
                    $data = array(
                        "status" => false,
                        'message' => "OTP is invalid"
                        
                    );
                    return Response()->json($data, $this->successStatus);   
                }
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "Your provided email does not exists."
                    
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

    // Resend Company OTP
    public function resendOtp(ResendOTPRequest $request){
        try {
            //DB::beginTransaction();
            $user = User::where('email',$request->email)->first();
            if($user){
                UsersOtp::where('user_id',$user->user_auth_id)->delete();
                $otp = $this->generateOTP();
                $userOTP = new UsersOtp();
                $userOTP->user_id = $user->user_auth_id;
                $userOTP->otp = $otp;
                $userOTP->expireDate = now()->addMinute(10);
                $userOTP->save();
                
                $userAuth = UserAuth::findOrFail($user->user_auth_id);
                
                // Send resent OTP in Email
                if($request->otp_type === 'signup'){
                    event(new SignupOtpEvent($userAuth->user));
                }
                else{
                    event(new ForgotPasswordOtpEvent($userAuth->user)); 
                }
                //DB::commit();
                $data = array(
                    "status" => true,
                    'message' => 'The otp has been send in your mail account. please check it inbox or spam. Thanks',
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "Your provided email does not exists."
                    
                );
                return Response()->json($data, $this->successStatus);
            }
        } catch (ApiException $e) {
            $data = array(
                "status" => false,
                'message' => "Something went wrong try later",
                "error" => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus);
            //throw new ApiException('Something went wrong try later', $e, 400, 400, 2003);
        } catch (\Exception $e) {
            $data = array(
                "status" => false,
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                "error" => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus);
        }
    }

    // Save company profile steps
    public function updateCompProfile(Request $request){
        try{
            if(isset($request->step)){
                $company = Company::select('id','company_name','company_email','company_phone','address','status','otp_verify','name','package_id','social_security','country_code','country','origination_number','vat_number','bankgiro','plusgiro','city','state','zipcode','step')->where('company_email',$request->email)->first();
                if($request->step == 1){
                    $validator = Validator::make($request->all(), [ 
                        'email' => 'required|email',
                        'full_name' => 'required',
                        // 'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:9',
                        'mobile' => 'required|numeric',
                        'social_security' => 'required',
                        'country_code' => 'required|numeric',
                        'country' => 'required',
                    ]);
                    if ($validator->fails()) { 
                        return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
                    }

                    $company->company_name = $request->full_name;
                    $company->company_phone = $request->mobile;
                    $company->social_security = $request->social_security;
                    $company->country_code = $request->country_code;
                    $company->country = $request->country;
                    $company->step = 2;
                    $company->save();

                    $user = User::where('company_id',$company->id)->first();
                    $user->name = $request->full_name;
                    $user->country_phonecode = $request->country_code;
                    $user->mobile = $request->mobile;
                    if ($request->hasFile('image')) {
                        $user->image = Files::uploadLocalOrS3($request->image, 'avatar', 300);
                    }
                    $user->save();
                    
                    $data = array(
                        "status" => true,
                        'message' => 'Profile has been saved successfully',
                        'company' => $company
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                else if($request->step == 2){
                    $validator = Validator::make($request->all(), [ 
                        'email' => 'required|email',
                        'business_name' => 'required',
                        'origination_number' => 'required',
                        'vat_number' => 'required',
                        'bankgiro' => 'required',
                        'address' => 'required',
                        'city' => 'required',
                        'state' => 'required',
                        'zipcode' => 'required',
                    ]);
                    if ($validator->fails()) { 
                        return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
                    }

                    $company->company_name = $request->business_name;
                    $company->app_name = $request->business_name;
                    $company->origination_number = $request->origination_number;
                    $company->vat_number = $request->vat_number;
                    $company->bankgiro = $request->bankgiro;
                    $company->plusgiro = $request->plusgiro;
                    $company->city = $request->city;
                    $company->state = $request->state;
                    $company->zipcode = $request->zipcode;
                    $company->address = $request->address;
                    $company->iban = $request->iban;
                    $company->bic = $request->bic;
                    $company->fskatt = $request->fskatt;
                    $company->step = 3;
                    $company->save();
                    $data = array(
                        "status" => true,
                        'message' => 'Profile has been saved successfully',
                        'company' => $company
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                else{
                    $validator = Validator::make($request->all(), [ 
                        'email' => 'required|email',
                        'package_id' => 'required|exists:packages,id',
                        'package_type' => 'required|in:monthly,annual',
                    ]);
                    if ($validator->fails()) { 
                        return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
                    }

                    $company->package_id = $request->package_id;
                    $company->package_type = $request->package_type;
                    $company->step = 4;
                    $company->save();

                    $user = User::where('company_id',$company->id)->first();
                    $user->trial_ends_at = now()->addMonth();
                    $user->save();

                    $expiry = now()->addYear();
                    $days = 365;
                    $minutes = 60 * 60 * $days;
                    $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];
                    Auth::loginUsingId($user->user_auth_id);
                    $currentUser = auth()->user();
                    $tokenName = Str::slug($currentUser->user->name . ' ' . $currentUser->user->id);
                    $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                    $client = User::allMyClients()->count();
                    $data = array(
                        "status" => true,
                        'message' => 'Profile has been saved successfully',
                        'company' => $company,
                        'client' => $client,
                        "token" => $token,
                        'expires' => $expiry->format('Y-m-d\TH:i:sP'),
                        'expires_in' => $minutes,
                    );
                    return Response()->json($data, $this->successStatus);
                }
            }

            $data = array(
                "status" => false,
                'message' => "Step is required"
                
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
                'message' => "Some error occurred when inserting the data. Please try again or contact support",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }

    }

    // Return All Company Packages
    public function getCompanyPackages(){
        $allPackages = Package::where('is_private',0)->whereNotIn('id',['1','2'])->orderBy('sort','asc')->get();
        $data = array(
            "status" => true,
            'message' => "Please choose package",
            'packages' => $allPackages
            
        );
        return Response()->json($data, $this->successStatus);
    }

    // Country List
    public function countryList(){
        $country = Country::select('id','name','iso','phonecode')->orderBy('name','asc')->get();
        $data = array(
            "status" => true,
            'countries' => $country
            
        );
        return Response()->json($data, $this->successStatus);
    }


}
