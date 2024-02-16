<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Entities\User;
use Froiden\RestAPI\ApiResponse;
use Froiden\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\RestAPI\Http\Requests\Auth\EmailVerifyRequest;
use Modules\RestAPI\Http\Requests\Auth\LoginRequest;
use Modules\RestAPI\Http\Requests\Auth\RefreshTokenRequest;
use Modules\RestAPI\Http\Requests\Auth\ForgotPasswordRequest;
use Modules\RestAPI\Http\Requests\Auth\ResetPasswordRequest;
use Modules\RestAPI\Entities\UsersOtp; 
use Modules\RestAPI\Entities\UserAuth;
use App\Models\Company;
use App\Events\SignupOtpEvent;
use App\Events\ForgotPasswordOtpEvent;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Hash;
use Validator;
use App\Models\EmployeeDetails;
use App\Models\Role;
use Illuminate\Support\Facades\File;

class AuthController extends ApiBaseController
{

    public function login(LoginRequest $request)
    {
        // Modifications to this function may also require modifications to
        $email = $request->get('email');
        $password = $request->get('password');
        $days = 365;
        $minutes = 60 * 60 * $days;
        $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];

        $check = auth()->attempt(['email' => $email, 'password' => $password]);

        if ($check) {
            $user = auth()->user()->user;

            if ($user && $user->status === 'deactive') {
                auth()->logout();
                $data = array(
                    "status" => false,
                    'message' => "User account disabled"
                    
                );
                return Response()->json($data, $this->forbiddenStatus);

                return ApiResponse::exception($exception);
            }

            if($user && $user->company_id !=''){
                $company = Company::findOrFail($user->company_id);
                if($company){
                    if($company->otp_verify == 'false'){
                        $otp = $this->generateOTP();

                        $companyOTP = new UsersOtp();
                        $companyOTP->user_id = $user->user_auth_id;
                        $companyOTP->otp = $otp;
                        $companyOTP->expireDate = now()->addMinute(10);
                        $companyOTP->save();

                        $userAuth = UserAuth::findOrFail($user->user_auth_id);
                        event(new SignupOtpEvent($userAuth->user));
                        $data = array(
                            "status" => true,
                            "otpScreen" => true,
                            'message' => 'The Otp has been send in your mail account. please check it inbox or spam. Thanks',
                            
                        );
                        return Response()->json($data, $this->successStatus);
                    }
                }
            }

            $expiry = now()->addYear();
            $tokenName = Str::slug($user->name . ' ' . $user->id);

            $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;


            if (isWorksuiteSaas() && $user->is_superadmin) {
                $data = array(
                    "status" => false,
                    'message' => "Sorry this app is not built for superadmin"
                    
                );
                return Response()->json($data, $this->unauthorisedStatus);
            }

            $client = User::allMyClients()->count();
            $user = auth()->user()->user;
            $userRoleName = $user->roles->first()->name;
            $file = File::get(public_path('user-uploads/roles-permissions/roles_permissions.json'));
            $fileData = json_decode($file, true);
            $permissionDatas = array_column($fileData, $userRoleName);
            $data = array(
                "status" => true,
                'message' => 'Logged in successfully',
                'token' => $token,
                'user' => $user->load('roles', 'roles.perms', 'roles.permissions'),
                'client' => $client,
                'expires' => $expiry->format('Y-m-d\TH:i:sP'),
                'expires_in' => $minutes,
                "rolePermission"=>$permissionDatas
            );
            return Response()->json($data, $this->successStatus);
        }
        

        $data = array(
            "status" => false,
            'message' => "Wrong credentials provided"
            
        );
        return Response()->json($data, $this->successStatus);
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();
        $data = array(
            "status" => true,
            'message' => 'Token invalidated successfully'
        );
        return Response()->json($data, $this->successStatus);
        //return ApiResponse::make('Token invalidated successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = $request->email;

        $user = User::where('email', $email)->first();
        if($user){
            $otp = $this->generateOTP();
            $userOTP = new UsersOtp();
            $userOTP->user_id = $user->user_auth_id;
            $userOTP->otp = $otp;
            $userOTP->expireDate = now()->addMinute(10);
            $userOTP->save();
            
            $userAuth = UserAuth::findOrFail($user->user_auth_id);
            //Create Password Reset Token
            
            // Send resent OTP in Email
            event(new ForgotPasswordOtpEvent($userAuth->user));
            //DB::commit();
            $data = array(
                "status" => true,
                'message' => 'The otp has been send in your mail account. please check it inbox or spam. Thanks'
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

        //return ApiResponse::make('If your email belongs to an account, a password reset email has been sent to it');
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $authUser = UserAuth::where('email', $request->email)->first();
        if($authUser){
            $authUser->password = $request->password;
            $authUser->save();
            DB::table('password_resets')->where('email',$request->email)->delete();
            $data = array(
                "status" => true,
                'message' => "Your password has been updated successfully."
                
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
        
    }
    public function refresh(RefreshTokenRequest $request)
    {
        $user = auth()->user();

        if ($user->status === 'inactive') {
            $this->logout();
            throw new ApiException('User account disabled', null, 403, 403, 2015);
        }

        $expiry = now()->addHour();
        $claims = $user->currentAccessToken()->claims;

        $currentToken = $user->currentAccessToken()->id;
        $tokenName = Str::slug($user->name . ' ' . $user->id);

        $newToken = $user->createToken($tokenName, ['*'], now()->addHour(), $claims)->plainTextToken;

        // Revoke Old Token
        $user->tokens()->where('id', $currentToken)->delete();

        return ApiResponse::make('Token refreshed successfully', [
            'token' => $newToken,
            'expires' => $expiry->format('Y-m-d\TH:i:sP'),
            'expires_in' =>  60, // 60 minutes
        ]);

    }

    public function verify(EmailVerifyRequest $request)
    {

        $user = Employee::where('email_verification_token', $request->token)
            ->whereNotNull('email_verification_token')
            ->first();

        if ($user) {
            DB::beginTransaction();

            $user->email_verification_token = null;
            $user->email_verified = 'yes';
            $user->save();

            $user->company->company_email_verified = 'yes';
            $user->company->save();

            event(new EmailVerificationSuccessEvent($user->company, $user));
            DB::commit();

            return ApiResponse::make('Success', ['status' => 'success']);
        }

        return ApiResponse::make('Token is expired', ['status' => 'fail']);
    }


    public function me()
    {
        $user = auth()->user()->user;
        $userRoleName = $user->roles->first()->name;
        $client = User::allMyClients()->count();
        $expired = false;
        $expiredMessage = '';
        $daysLeftInTrial = now(company()->timezone)->diffInDays(\Carbon\Carbon::parse(company()->licence_expire_on), false);
        if($daysLeftInTrial < 0){
            if(in_array(company()->package->default, ['trial'])){
                $expiredMessage =  __('superadmin.packages.trialExpiredMessage');
            }
            else{  
                $expiredMessage=  __('superadmin.packages.packageExpiredMessage');  
            }
            $expired = true;
        }
        $file = File::get(public_path('user-uploads/roles-permissions/roles_permissions.json'));
        $fileData = json_decode($file, true);
        $permissionDatas = array_column($fileData, $userRoleName);
        $data = array(
            "status" => true,
            'user' => $user->load('roles', 'roles.perms', 'roles.permissions'),
            'package_expired' => $expired,
            'expired_message' => $expiredMessage,
            'client' => $client,
            "rolePermission"=>$permissionDatas
        );
        return Response()->json($data, $this->successStatus);
        // return ApiResponse::make('Auth User', [
        //     'data' => auth()->user()->load('roles', 'roles.perms', 'roles.permissions'),
        // ]);
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

    // Verify user invitation link
    public function verifyInvitationLink($code){
        try {
            $invite = UserInvitation::where('invitation_code', $code)->first();
            if($invite){
                if($invite->status == 'active'){
                    $data = array(
                        "status" => true,
                        'invite' => $invite,
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                if($invite->status == 'expired'){
                    $data = array(
                        "status" => false,
                        'message' => 'Opps! This invitation link is expired.',
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                if($invite->status == 'inactive'){
                    $data = array(
                        "status" => false,
                        'message' => 'Opps! This invitation link is inactive. please contact to company owner',
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
            }
            $data = array(
                "status" => false,
                'message' => 'Opps! This invitation link is not valid.',
                
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
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }
    }

    // User/Member Signup Via Invitation Link
    public function memberSignupViaInvitation(Request $request, $code){
        try{
            DB::beginTransaction();
            $expiry = now()->addYear();
            $days = 365;
            $minutes = 60 * 60 * $days;
            $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];
            $validator = Validator::make($request->all(), [ 
                'email' => 'required|max:255|email',
                'password' => 'required|string|min:6|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/',
            ]);
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }

            $invite = UserInvitation::where('invitation_code', $code)->first();
            if (is_null($invite) || ($invite->invitation_type == 'email' && $request->email != $invite->email)) {
                $data = array(
                    "status" => true,
                    'message' => 'Invalid invitation. You are not authorised to sign up.',
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            if($invite){
                if($invite->status == 'active'){

                    $userAuth = UserAuth::createUserAuthCredentials($request->email, $request->password);

                    $user = new User();
                    $user->name = $invite->name;
                    $user->company_id = $invite->company_id;
                    $user->email = $request->email;
                    $user->user_auth_id = $userAuth->id;
                    $user->save();
                    $user = $user->setAppends([]);

                    $lastEmployeeID = EmployeeDetails::where('company_id', $invite->company_id)->count();
                    $checkifExistEmployeeId = EmployeeDetails::select('id')->where('employee_id', ($lastEmployeeID + 1))->where('company_id', $invite->company_id)->first();
                    $company = Company::findOrFail($invite->company_id);
                    if ($user->id) {
                        /* $employee = new EmployeeDetails();
                        $employee->user_id = $user->id;
                        $employee->company_id = $invite->company_id;
                        $employee->employee_id = ((!$checkifExistEmployeeId) ? ('EMP-'.$lastEmployeeID + 1) : null);
                        $employee->joining_date = now($company->timezone)->format('Y-m-d');
                        $employee->added_by = $user->id;
                        $employee->last_updated_by = $user->id;
                        $employee->save(); */
                        //new code add
                        $employee = new EmployeeDetails();
                        $employee->user_id = $user->id;
                        $employee->company_id = $invite->company_id;
                        $employee->employee_id = ((!$checkifExistEmployeeId) ? ($lastEmployeeID + 1) : null);
                        $employee->joining_date = now($company->timezone)->format('Y-m-d');
                        $employee->added_by = $user->id;
                        $employee->last_updated_by = $user->id;
                        $employee->invitation_id = $invite->id;
                        $employee->save();
                        UserInvitation::where('id', $invite->id)
                            ->update([
                                'name' => $request->name
                                ]);
                    }
                    $userRole = Role::findOrFail($invite->role_id);
                    $user->attachRole($userRole);

                    $user->insertUserRolePermission($userRole->id);
                    /* $invite->status = 'expired';
                    $invite->save(); */
                    //new code add
                    if ($invite->invitation_type == 'email') {
                        $invite->status = 'inactive';
                        $invite->save();
                    }
                    
                    DB::commit();
                    $check = auth()->attempt(['email' => $request->email, 'password' => $request->password]);
                    if ($check) {
                        $user = auth()->user()->user;
                        $userRoleName = $user->roles->first()->name;
                        $file = File::get(public_path('user-uploads/roles-permissions/roles_permissions.json'));
                        $fileData = json_decode($file, true);
                        $permissionDatas = array_column($fileData, $userRoleName);
                        $tokenName = Str::slug($user->name . ' ' . $user->id);
                        $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                        $data = array(
                            "status" => true,
                            'message' => 'Logged in successfully',
                            'token' => $token,
                            'user' => $user->load('roles', 'roles.perms', 'roles.permissions'),
                            'expires' => $expiry->format('Y-m-d\TH:i:sP'),
                            'expires_in' => $minutes,
                            "rolePermission"=>$permissionDatas
                            
                        );
                        return Response()->json($data, $this->successStatus);
                    }
                    else{
                        $data = array(
                            "status" => true,
                            'message' => 'Your signup has been completed. please try to login',
                            
                        );
                        return Response()->json($data, $this->successStatus);
                    }
                }
                if($invite->status == 'expired'){
                    $data = array(
                        "status" => false,
                        'message' => 'Opps! This invitation link is expired.',
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                if($invite->status == 'inactive'){
                    $data = array(
                        "status" => false,
                        'message' => 'Opps! This invitation link is inactive. please contact to company owner',
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
            }
            $data = array(
                "status" => false,
                'message' => 'Opps! This invitation link is not valid.',
                
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

}
