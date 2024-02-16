<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Http\Controllers\BaseController;
use Modules\RestAPI\Entities\Project;
use App\Models\ProjectMember;
use Modules\RestAPI\Entities\ProjectArticle;
use Validator;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;
use Modules\RestAPI\Http\Requests\Projects\CreateRequest;
use App\Models\EmployeeDetails;
use App\Models\ProjectSetting;
use Modules\RestAPI\Entities\User;
use Modules\RestAPI\Entities\Product;

class MyProjectController extends ApiBaseController {

    protected $model = '';

    // Get My Project function
    public function myProjects(Request $request){
        $user = auth()->user()->user;
        $userDetails = User::findOrFail($user->id);
        $userRoleName = $userDetails->roles->first()->name;
        if($userRoleName == 'employee' || $userRoleName == 'contractor'){
            $query = ProjectMember::join('projects','projects.id', '=', 'project_members.project_id')->where('user_id',$user->id);
        }
        else{
            $query = Project::with('client','articles')->where('company_id',$user->company_id)->where('added_by',$user->id);

        }
        if(!empty($request->project_name)){
            $query->where('project_name', 'LIKE', '%'.$request->project_name.'%');
        }
        if(!empty($request->project_type)){
            $query->where('project_type', $request->project_type);
        }
        if(!empty($request->status)){
            $query->where('status', $request->status);
        }
        if(!empty($request->client_name)){
            $searchString = $request->client_name;
            $query->whereHas('client', function ($query) use ($searchString){
                $query->where('name', 'like', '%'.$searchString.'%');
            });
        }
        if(!empty($request->favourite)){
            if($request->favourite == 1){
                $query->where('favourite', $request->favourite);
            }
        }
        $projects = $query->paginate($request->per_page);
        $data = array(
            "status" => true,
            'projects' => $projects
            
        );
        return Response()->json($data, $this->successStatus);
    }
     // Get all My Project id and name function
     public function allMyProjects(Request $request){
        $user = auth()->user()->user;
        $userDetails = User::findOrFail($user->id);
        $userRoleName = $userDetails->roles->first()->name;
        if($userRoleName == 'employee' || $userRoleName == 'contractor'){
            $query = ProjectMember::join('projects','projects.id', '=', 'project_members.project_id')->select('projects.id','projects.project_name')->where('user_id',$user->id)->get();
        }
        else{
            $query = Project::where('company_id',$user->company_id)->where('added_by',$user->id)->get();
            $projectDataArray = array();
            foreach ($query as $key=>$projectData){
                
                $projectDataArray[$key]["value"]= $projectData->id;
                $projectDataArray[$key]["label"]= $projectData->project_name;

            }
            $query = $projectDataArray;

        }
        $projects = $query;
        $data = array(
            "status" => true,
            'projects' => $projects
            
        );
        return Response()->json($data, $this->successStatus);
    }
    public function createProjects(Request $request){
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
            // Active Team members
            $teamMemberDetails = EmployeeDetails::where('company_id',$user->company_id)->get(['id','user_id']);
            if($teamMemberDetails){
                foreach($teamMemberDetails as $key=>$teamMemberDetail){
                    $userDetails = User::findOrFail($teamMemberDetail->user_id);

                    $teamMemberDetails[$key]->text = $userDetails->name;
                }
            }
            $data = array(
                "status" => true,
                'clients' => $activeClients,
                'products' => $products,
                'team_members' => $teamMemberDetails,
                
                
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
    // Add New Project
    public function addMyProject(CreateRequest $request){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $projectCount = Project::where('company_id',$user->company_id)->count();
            $company = company();
            if (!is_null($company) && ($company->package->project_unlimited == 'false') && $projectCount >= $company->package->max_project) {
                $data = array(
                    "status" => false,
                    'message' => __('superadmin.maxProjectsLimitReached'),
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            //return $user;
            $project = new Project();
            $project->company_id = $user->company_id;
            $project->project_name = $request->project_name;
            $project->start_date = $request->start_date;
            $project->deadline = $request->end_date;
            $project->client_id = $request->client_id;
            $project->project_budget = $request->total_budget;
            $project->currency_id = $request->currency_id;
            $project->hours_allocated = $request->total_hours;
            $project->status = $request->status;
            $project->project_type = $request->project_type;
            $project->added_by = $user->id;
            $project->save(); 

            // Save project Members
            if(isset($request->project_members) && count($request->project_members) > 0){
                foreach($request->project_members as $key=>$member){ 

                    $projectMember = new ProjectMember();
                    $projectMember->user_id = $member;
                    $projectMember->project_id = $project->id;
                    $projectMember->added_by = $user->id;
                    $projectMember->save();
                }
            }

            // Save project Articles
            if(isset($request->project_articles) && count($request->project_articles) > 0){
                foreach($request->project_articles as $key=>$article){ 

                    $projectArticle = new ProjectArticle();
                    $projectArticle->article_id = $article;
                    $projectArticle->project_id = $project->id;
                    $projectArticle->added_by = $user->id;
                    $projectArticle->save();
                }
            }
            // Save project Setting
            $projectSetting = new ProjectSetting();
            $projectSetting->project_id = $project->id;
            $projectSetting->company_id = $user->company_id;
            $projectSetting->setting_project_type = $request->setting_project_type;
            $projectSetting->setting_estimate_type = $request->setting_estimate_type;
            $projectSetting->setting_rate_type = $request->setting_rate_type;
            $projectSetting->setting_hours_rate = $request->setting_hours_rate;
            $projectSetting->setting_cost_rate = $request->setting_cost_rate;
            $projectSetting->setting_expense_grand_total = $request->setting_expense_grand_total;
            $projectSetting->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'The project added successfully. Thanks',
                
            );
            return Response()->json($data, $this->successStatus); 
            
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

    //Edit Project
    public function editProject($id){
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
        // Active Team members
        $teamMemberDetails = EmployeeDetails::where('company_id',$user->company_id)->get(['id','user_id']);
        if($teamMemberDetails){
            foreach($teamMemberDetails as $key=>$teamMemberDetail){
                $userDetails = User::findOrFail($teamMemberDetail->user_id);

                $teamMemberDetails[$key]->text = $userDetails->name;
            }
        }
        $projectDetails = Project::with('client')->findOrFail($id);
        if($projectDetails){
            $projectSetting = ProjectSetting::where('project_id',$id)->where('company_id',$user->company_id)->first();
            $projectArticle = ProjectArticle::where('project_id',$id)->get();
            $data = array(
                "status" => true,
                'project' => $projectDetails,
                'project_article' => $projectArticle,
                'project_setting' => $projectSetting,
                'active_clients' => $activeClients,
                'products' => $products,
                'team_members' => $teamMemberDetails
                
            );
            return Response()->json($data, $this->successStatus);

        }
        else{
            $data = array(
                "status" => false,
                'message' => 'project not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    //update project 
    public function updateMyProject(Request $request, $id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $project = Project::where('id',$id)->where('company_id',$user->company_id)->first();
            if($project){
            $project->company_id = $user->company_id;
            $project->project_name = $request->project_name;
            $project->start_date = $request->start_date;
            $project->deadline = $request->end_date;
            $project->client_id = $request->client_id;
            $project->project_budget = $request->total_budget;
            $project->currency_id = $request->currency_id;
            $project->hours_allocated = $request->total_hours;
            $project->status = $request->status;
            $project->project_type = $request->project_type;
            $project->added_by = $user->id;
            $project->save(); 

            // Save project Members
            if(isset($request->project_members) && count($request->project_members) > 0){
                ProjectMember::where('project_id',$id)->delete();
                foreach($request->project_members as $key=>$member){ 

                    $projectMember = new ProjectMember();
                    $projectMember->user_id = $member;
                    $projectMember->project_id = $project->id;
                    $projectMember->added_by = $user->id;
                    $projectMember->save();
                }
            }

            // Save project Articles
            if(isset($request->project_articles) && count($request->project_articles) > 0){
                ProjectArticle::where('project_id',$id)->delete();
                foreach($request->project_articles as $key=>$article){ 

                    $projectArticle = new ProjectArticle();
                    $projectArticle->article_id = $article;
                    $projectArticle->project_id = $project->id;
                    $projectArticle->added_by = $user->id;
                    $projectArticle->save();
                }
            }
            // Save project Setting
            $projectSetting = ProjectSetting::where('project_id',$id)->where('company_id',$user->company_id)->first();
            $projectSetting->project_id = $project->id;
            $projectSetting->company_id = $user->company_id;
            $projectSetting->setting_project_type = $request->setting_project_type;
            $projectSetting->setting_estimate_type = $request->setting_estimate_type;
            $projectSetting->setting_rate_type = $request->setting_rate_type;
            $projectSetting->setting_hours_rate = $request->setting_hours_rate;
            $projectSetting->setting_cost_rate = $request->setting_cost_rate;
            $projectSetting->setting_expense_grand_total = $request->setting_expense_grand_total;
            $projectSetting->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'The project update successfully. Thanks',
                
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

    // Get My Project Team Members
    public function getMyTeamMembers(){
        try{

            $teamMembers = User::allCompanyEmployee();
            $data = array(
                "status" => true,
                'members' => $teamMembers
                
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
    public function deleteProjects(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                $project = Project::where('id',$id)->where('company_id',$user->company_id)->first();
                if($project){
                    $project->deleted_at = now();
                    $project->save();   
                }
            }

            $data = array(
                "status" => true,
                'message' => 'The project has been deleted successfully'                
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
    public function updateStatusProject(Request $request, $id){
        
        $user = auth()->user()->user;
        if((isset($request->favourite) && !empty($request->favourite)) || $request->favourite == 0){
            $validator = Validator::make($request->all(), [ 
                'favourite' => 'required|in:0,1',
            ]);
            
        }
        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        
        $project = Project::where('id',$id)->where('company_id',$user->company_id)->first();
       if($project){
            if((isset($request->favourite) && !empty($request->favourite)) || $request->favourite == 0){
                $project->favourite = $request->favourite;
                $project->save();
                
                if($request->favourite == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'The project has been mark favourite successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'The project has been removed from favourite successfully. Thanks'                
                    );
                }
                return Response()->json($data, $this->successStatus);
            }
            

        }
        else{
            $data = array(
                "status" => false,
                'message' => "Project not found",
                
            );
            return Response()->json($data, $this->successStatus);
        }

    }

}
