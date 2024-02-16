<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\EmployeeDetails;
use App\Models\User;
use App\Models\UserInvitation;
use App\Models\Role;
use App\Models\RoleUser;
use App\Scopes\ActiveScope;
use Froiden\RestAPI\ApiController;
use Froiden\RestAPI\ApiResponse;
use Modules\RestAPI\Entities\Employee;
use Modules\RestAPI\Http\Requests\Employee\CreateRequest;
use Modules\RestAPI\Http\Requests\Employee\DeleteRequest;
use Modules\RestAPI\Http\Requests\Employee\IndexRequest;
use Modules\RestAPI\Http\Requests\Employee\ShowRequest;
use Modules\RestAPI\Http\Requests\Employee\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Helper\Files;
use Validator;

class EmployeeController extends ApiBaseController
{
    protected $model = Employee::class;

    protected $indexRequest = IndexRequest::class;

    protected $storeRequest = CreateRequest::class;

    protected $updateRequest = UpdateRequest::class;

    protected $showRequest = ShowRequest::class;

    protected $deleteRequest = DeleteRequest::class;

    public function modifyIndex($query)
    {
        return $query->visibility();
    }

    public function modifyShow($query)
    {
        return $query->withoutGlobalScope(ActiveScope::class);
    }

    public function modifyDelete($query)
    {
        return $query->withoutGlobalScope(ActiveScope::class);
    }

    public function modifyUpdate($query)
    {
        return $query->withoutGlobalScope(ActiveScope::class);
    }

    public function stored(Employee $employee)
    {
        $employeeDetail = request()->all('employee_detail')['employee_detail'];
        $employee->employeeDetail()->create($employeeDetail);

        // To add custom fields data
        if (request()->get('custom_fields_data')) {
            $employee->employeeDetail()->updateCustomFieldData(request()->get('custom_fields_data'));
        }

        $employeeRole = Role::where('name', 'employee')->first();
        $employee->attachRole($employeeRole);

        return $employee;
    }

    public function updating(Employee $employee)
    {
        $data = request()->all('employee_detail')['employee_detail'];
        $data['department_id'] = $data['department']['id'];
        $data['designation_id'] = $data['designation']['id'];
        unset($data['designation']);
        unset($data['department']);
        $employee->employeeDetail()->update($data);

        return $employee;
    }

    //phpcs:ignore
    public function lastEmployeeID()
    {
        $lastEmployeeID = EmployeeDetails::max('id');

        return ApiResponse::make(null, ['id' => $lastEmployeeID]);
    }
    //get all team member
    public function getAllTeamMember(Request $request){
        $user = auth()->user()->user;
        $query =DB::table('user_invitations')
            ->leftJoin('employee_details', 'user_invitations.id', '=', 'employee_details.invitation_id')
            ->select('user_invitations.id AS invite_id','user_invitations.name','user_invitations.email','user_invitations.status','user_invitations.role_id','employee_details.*')->where('user_invitations.company_id',$user->company_id);
            
        if(!empty($request->name)){
            $query->where('user_invitations.name','LIKE',$request->name);
        }
        if(!empty($request->email)){
            $query->where('user_invitations.email','LIKE',$request->email);
        }
        if(!empty($request->role_id)){
            $query->where('user_invitations.role_id','LIKE',$request->role_id);

        }
        if(!empty($request->type)){
            $query->where('employee_details.employment_type','LIKE',$request->type);

        }
        if(!empty($request->search)){
            $query->where('user_invitations.name', 'LIKE' ,'%'.$request->search.'%')
            ->orWhere('user_invitations.email', 'LIKE' ,'%'.$request->search.'%')
            ->orWhere('employee_details.employment_type', 'LIKE' ,'%'.$request->search.'%');

        }
        $teamMembers = $query->orderBy('user_invitations.id','desc')->paginate($request->per_page);
       foreach($teamMembers as $key=>$teamMember){
            if(!empty($teamMember->user_id)){

                $userDetails = User::findOrFail($teamMember->user_id);
                $userRoleName = $userDetails->roles->first()->name;
                $userDetails->roleName = $userRoleName;
                $teamMembers[$key]->user = $userDetails;
            }
            else{
                $roles = Role::select('name','display_name')->where('company_id',$user->company_id)->where('id',$teamMember->role_id)->first();
                $teamMembers[$key]->user = null;
                $teamMembers[$key]->userRoleName = $roles;
            }
        }
        $allRoles = Role::select('id','company_id','name','display_name')->where('company_id',$user->company_id)->get();
        $data = array(
            "status" => true,
            'teamMember' => $teamMembers,
            'teamMemberRoles' => $allRoles
            
        );
        return Response()->json($data, $this->successStatus); 
    }

    public function editTeamMember(Request $request,$id){
        $user = auth()->user()->user;
        $teamMemberDatils = EmployeeDetails::with('user')->where('company_id',$user->company_id)->where('id',$id)->first();
        $userDetails = User::findOrFail($teamMemberDatils->user_id);
        $userRoleName = $userDetails->roles->first()->name;
        $userRoleId = $userDetails->roles->first()->id;
        $roles = Role::select('id','company_id','name','display_name')->where('company_id',$user->company_id)->get();
        if($teamMemberDatils){
        $data = array(
            "status" => true,
            'teamMemberDatils' => $teamMemberDatils,
            'roleName'=>$userRoleName,
            'roleId'=>$userRoleId,
            'roles'=>$roles
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'team member not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    public function updateTeamMember(Request $request,$id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $teamMemberDetails = EmployeeDetails::findOrFail($id);
            $userDetails = User::findOrFail($teamMemberDetails->user_id);
            $roleDetails = RoleUser::where('user_id',$teamMemberDetails->user_id)->first();
            if($teamMemberDetails){
                    $teamMemberDetails->hourly_rate = $request->set_billable_rate;
                    $teamMemberDetails->hours_cost_rate = $request->set_cost_rate;
                    $teamMemberDetails->employment_type = $request->employee_type;
                    $teamMemberDetails->address = $request->address;
                    $teamMemberDetails->save();
                    if ($request->hasFile('profile_pic')) {
                            Files::createDirectoryIfNotExist('team_member/attachments');
                            $imageName = Files::uploadLocalOrS3($request->profile_pic, 'team_member/attachments/', 300);
                            $userDetails->image = $imageName;
                    }
                    else{
                        $userDetails->image =  $userDetails->image;
    
                    }
                    $userDetails->name = $request->name;
                    $userDetails->mobile = $request->contact_number;
                    $userDetails->save();
                    $userDetails->roles()->sync([$request->role_id]);
                    UserInvitation::where('id', $teamMemberDetails->invitation_id)
                    ->update([
                        'role_id' => $request->role_id,
                        'name' => $request->name
                        ]);
                    DB::commit();
                    $data = array(
                        "status" => true,
                        'message' => 'Team member update successfully'
                        
                    );
                    return Response()->json($data, $this->successStatus);

                
                
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Team member not found'
                    
                );
                return Response()->json($data, $this->successStatus);

            }
        }catch (\Exception $e) {
            DB::rollback();
            $data = array(
                "status" => false,
                'message' => "Some error occurred when inserting the data. Please try again or contact support.",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }

    }
    public function deleteTeamMember(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){

                $emp_details = EmployeeDetails::where('user_id',$id)->where('company_id',$user->company_id)->first();
                if($emp_details){
                    $teamMember = User::where('id',$id)->where('company_id',$user->company_id)->first();
                    UserInvitation::where('id',$emp_details->invitation_id)->delete();
                    EmployeeDetails::where('user_id',$id)->delete();
                    $teamMember->deleted_at = now();
                    $teamMember->save();
                }
                else{
                    UserInvitation::findOrFail($id)->delete();
                }
            }

            $data = array(
                "status" => true,
                'message' => 'The team member has been deleted successfully'                
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
