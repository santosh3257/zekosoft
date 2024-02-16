<?php

namespace Modules\RestAPI\Http\Controllers;

use Froiden\RestAPI\ApiController;
use App\Models\EmployeeDetails;
use App\Models\Role;
use App\Models\UniversalSearch;
use App\Models\User;
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
use Illuminate\Support\Facades\DB;
use Modules\RestAPI\Entities\UsersOtp; 
use Modules\RestAPI\Http\Requests\Signup\VerifyOTPRequest;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialLoginController extends ApiBaseController {

    public function socialLogin(Request $request){
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [ 
                'email' => 'required|email',
                'provider_type' => 'required|in:facebook,gmail',
                'provider_id' => 'required',
                'provider_token' => 'required',
            ]);
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $alreadyRegistered = UserAuth::where('email',$request->email)->first();
            if($alreadyRegistered){
                if($alreadyRegistered->provider_id == $request->provider_id){
                    $alreadyRegistered->provider_token = $request->provider_token;
                    $alreadyRegistered->save();
                    $user = User::where('email',$request->email)->first();
                    $expiry = now()->addYear();
                    $days = 365;
                    $minutes = 60 * 60 * $days;
                    $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];
                    Auth::loginUsingId($user->user_auth_id);
                    $currentUser = auth()->user();
                    $tokenName = Str::slug($currentUser->user->name . ' ' . $currentUser->user->id);
                    $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                    DB::commit();
                    $data = array(
                        "status" => true,
                        'message' => 'Logged in successfully',
                        'token' => $token,
                        'user' => $user->load('roles', 'roles.perms', 'roles.permissions'),
                        'expires' => $expiry->format('Y-m-d\TH:i:sP'),
                        'expires_in' => $minutes,
                    );
                    return Response()->json($data, $this->successStatus);
                }

                $data = array(
                    "status" => false,
                    'message' => "Your Provided id does not match"
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{

                $validator = Validator::make($request->all(), [ 
                    'email' => 'required|unique:users|email|max:255',
                ]);
                if ($validator->fails()) { 
                    return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
                }
                $company = new Company();
                $company->company_name = $request->name;
                $company->app_name = $request->company_name;
                $company->company_email = $request->email;
                $company->company_phone = $request->company_phone;
                $company->website = $request->website;
                $company->address = $request->address;
                $company->timezone = $request->timezone;
                $company->locale = $request->locale;
                $company->package_id = 2;
                $company->currency_id = 1;
                $company->trial_ends_at = now()->addMonth();
                $company->licence_expire_on = now()->addMonth();
                $company->package_type = 'monthly';
                $company->subscription_updated_at = now();
                $company->status = 'active';
                $company->otp_verify = 'true';

                if($request->has('approved')){
                    $company->approved = $request->approved;
                }

                if ($request->hasFile('logo')) {
                    $company->logo = Files::upload($request->logo, 'app-logo');
                    $company->light_logo = $company->logo;
                }

                $company->save();

                $userId = $this->addUser($company, $request);
                DB::commit();
                $user = User::where('user_auth_id',$userId)->first();
                
                Auth::loginUsingId($user->user_auth_id);
                $currentUser = auth()->user();
                $expiry = now()->addYear();
                $days = 365;
                $minutes = 60 * 60 * $days;
                $claims = ['exp' => (int)now()->addYear()->getTimestamp(), 'remember' => 1, 'type' => 1];
                $tokenName = Str::slug($currentUser->user->name . ' ' . $currentUser->user->id);
                $token = auth()->user()->createToken($tokenName, ['*'], $expiry, $claims)->plainTextToken;
                $data = array(
                    "status" => true,
                    'message' => 'Logged in successfully',
                    'token' => $token,
                    'user' => $user->load('roles', 'roles.perms', 'roles.permissions'),
                    'expires' => $expiry->format('Y-m-d\TH:i:sP'),
                    'expires_in' => $minutes,
                );
                return Response()->json($data, $this->successStatus);

            }

        }catch (ApiException $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e
                
            );
            return Response()->json($data, $this->successStatus); 
        } catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e
                
            );
            return Response()->json($data, $this->successStatus); 
        }
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

        $userAuth->provider_type = $request->provider_type;
        $userAuth->provider_id = $request->provider_id;
        $userAuth->provider_token = $request->provider_token;
        $userAuth->save();

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

}
