<?php

namespace Modules\RestAPI\Http\Controllers;

use Froiden\RestAPI\ApiResponse;
use App\Models\Role;
use Modules\RestAPI\Entities\User;
use Validator;
use Modules\RestAPI\Http\Requests\SendInvitation\SendInvitationRequest;
use Illuminate\Http\Request;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\DB;

class CompanyController extends ApiBaseController
{
    public function company()
    {
        $company = api_user()->company;
        $company->makeHidden('card_last_four', 'stripe_id', 'card_brand', 'trial_ends_at');

        return ApiResponse::make('Application data fetched successfully', $company->toArray());
    }

    public function getCompanyRoles(){
        $user = auth()->user()->user;
        $roles = Role::select('id','company_id','name','display_name')->where('company_id',$user->company_id)->get();
        $data = array(
            "status" => true,
            'roles' => $roles,
            
        );
        return Response()->json($data, $this->successStatus);
    }

    public function companySendInvitation(SendInvitationRequest $request){
        try {
            DB::beginTransaction();
            $user = auth()->user()->user;
            $checkUser = UserInvitation::where('company_id',$user->company_id)->where('email',$request->email)->first();
            if($checkUser){
                $data = array(
                    "status" => false,
                    'message' => 'you already invite of this user. Thanks',
                    
                );
                return Response()->json($data, $this->successStatus);

            }
            else{
            $invite = new UserInvitation();
            $invite->user_id = $user->id;
            $invite->role_id = $request->role_id;
            $invite->name = $request->name;
            $invite->email = $request->email;
            $invite->message = $request->message;
            $invite->invitation_type = 'email';
            $invite->redirect_url = $request->redirectUrl;
            $invite->invitation_code = sha1(time() . $user->id);
            $invite->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Invitation mail has been send to the user. Thanks',
                
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
}
