<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Entities\ProjectTimeLog;
use Modules\RestAPI\Entities\Project;
use Modules\RestAPI\Entities\Product;
use App\Models\EmployeeDetails;
use Modules\RestAPI\Entities\User;
use Modules\RestAPI\Http\Requests\TimeLog\CreateRequest;
use Modules\RestAPI\Http\Requests\TimeLog\IndexRequest;
use Modules\RestAPI\Http\Requests\TimeLog\UpdateRequest;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiController;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use DateTime;
use DateInterval;

class TimeLogController extends ApiBaseController
{
    protected $model = ProjectTimeLog::class;

    protected $indexRequest = IndexRequest::class;

    protected $storeRequest = CreateRequest::class;

    protected $updateRequest = UpdateRequest::class;

    public function modifyIndex($query)
    {
        return $query->visibility();
    }

    public function storing(ProjectTimeLog $projectTimeLog)
    {
        $projectTimeLog->user_id = api_user()->id;
        $projectTimeLog->start_time = now();

        return $projectTimeLog;
    }

    public function updating(ProjectTimeLog $projectTimeLog)
    {
        $startTime = $projectTimeLog->start_time;
        $endTime = now();
        $totalHours = $endTime->diff($startTime)->format('%d') * 24 + $endTime->diff($startTime)->format('%H');

        $projectTimeLog->total_hours = $totalHours;
        $projectTimeLog->total_minutes = ($totalHours * 60) + ($endTime->diff($startTime)->format('%i'));
        $projectTimeLog->edited_by_user = api_user()->id;
        $projectTimeLog->end_time = $endTime;

        return $projectTimeLog;
    }

    public function me()
    {
        app()->make($this->indexRequest);

        $query = $this->parseRequest()
            ->addIncludes()
            ->addFilters()
            ->addOrdering()
            ->addPaging()
            ->getQuery();

        $user = api_user();

        $query->with('task')
            ->whereNull('end_time')
            ->where('user_id', $user->id);

        // Load employees relation, if not loaded
        $relations = $query->getEagerLoads();

        $relationRequested = true;

        $query->setEagerLoads($relations);

        /** @var Collection $results */
        $results = $this->getResults();

        $results = $results->toArray();
        $results = $results ? $results[0] : [];

        $meta = $this->getMetaData();

        return ApiResponse::make(null, $results, $meta);
    }
    public function addTimeTracker(Request $request){
        try{
            DB::beginTransaction();
            if($request->time_tracker_type == 'project'){
                $validator = Validator::make($request->all(), [ 
                    'project_id' => 'required',
                    'time' => 'required',
                ]);
            }
            else{
                $validator = Validator::make($request->all(), [ 
                    'client_id' => 'required',
                    'time' => 'required',
                ]);

            }
            if ($validator->fails()) { 
                return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $user = auth()->user()->user;
            $timeLog = new ProjectTimeLog();
            $timeLog->company_id = $user->company_id;
            if($request->time_tracker_type == 'project'){
                $timeLog->project_id = $request->project_id;
            }
            else{
                $timeLog->client_id = $request->client_id;

            }
            $date = Carbon::now()->format('Y/m/d');
            $timeLog->time_tracker_type = $request->time_tracker_type;
            if(!empty($request->team_member_id)){
                $timeLog->team_member_id = $request->team_member_id;
            }
            if(!empty($request->date)){
                $timeLog->time_tracking_date = $request->date;
            }
            else{
                $timeLog->time_tracking_date = $date;
            }
            $timeLog->article_id = $request->article_id;
            $timeLog->edited_by_user = $user->id;
            $timeLog->user_id = $user->id;
            $timeLog->start_time = now();
            $timeLog->end_time = now();
            $timeLog->client_project_time = $request->time;
            $timeLog->memo = $request->note;
            if(!empty($request->billable)){
                $timeLog->billable = $request->billable;
            }
            else{
                $timeLog->billable = 'no';
            }
            $timeLog->hourly_rate = 0;
            $timeLog->earnings = 0;
            $timeLog->save();
            DB::commit();
                $data = array(
                    "status" => true,
                    'message' => 'Your time log added successfully. Thanks'                
                );
                return Response()->json($data, $this->successStatus);  
        }catch (ApiException $e) {
                $data = array(
                    "status" => false,
                    'message' => "Something went worng try later",
                    'error' => $e->getMessage()
                    
                );
                return Response()->json($data, $this->successStatus); 
            }
        
    }
    public function getAllTimeLogs(Request $request){
        $user = auth()->user()->user;
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
        $query = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember','projectDetails','clientDetails')->where('company_id',$user->company_id)->where('added_by',$user->id);
        if(!empty($request->fetch_record)){
            if($request->fetch_record == 'day'){
                $query->whereDate('created_at', Carbon::today());
                if(!empty($request->logged_by)){
                    $query->where('team_member_id', $request->logged_by);
                }
            }
            if($request->fetch_record == 'week'){
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                if(!empty($request->logged_by)){
                    $query->where('team_member_id', $request->logged_by);
                }
            }
            if($request->fetch_record == 'month'){
                $query->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month);
                if(!empty($request->logged_by)){
                    $query->where('team_member_id', $request->logged_by);
                }
            }
        }
        $getAllLogs = $query->paginate($request->per_page);
        $data = array(
            "status" => true,
            'timeLogs' => $getAllLogs,
            
        );
        return Response()->json($data, $this->successStatus);
        

    }
    public function editTimelog(Request $request, $id){
        $user = auth()->user()->user;
        $getAllLogs = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember','projectDetails','clientDetails')->where('company_id',$user->company_id)->where('added_by',$user->id)->where('id',$id)->first();
        if($getAllLogs){
        $data = array(
            "status" => true,
            'timeLogs' => $getAllLogs
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'time log not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    public function updateTimelog(Request $request,$id){
        try{
            DB::beginTransaction();
            if($request->time_tracker_type == 'project'){
                $validator = Validator::make($request->all(), [ 
                    'project_id' => 'required',
                    'time' => 'required',
                ]);
            }
            else{
                $validator = Validator::make($request->all(), [ 
                    'client_id' => 'required',
                    'time' => 'required',
                ]);

            }
            if ($validator->fails()) { 
                return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $user = auth()->user()->user;
            $timeLog = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember')->where('company_id',$user->company_id)->where('added_by',$user->id)->where('id',$id)->first();
            if($timeLog){
                $timeLog->company_id = $user->company_id;
            if($request->time_tracker_type == 'project'){
                $timeLog->project_id = $request->project_id;
                $timeLog->client_id = null;
            }
            else{
                $timeLog->client_id = $request->client_id;
                $timeLog->project_id = null;
            }
            $timeLog->time_tracker_type = $request->time_tracker_type;
            $timeLog->team_member_id = $request->team_member_id;
            $timeLog->time_tracking_date = $request->date;
            $timeLog->article_id = $request->article_id;
            $timeLog->edited_by_user = $user->id;
            $timeLog->user_id = $user->id;
            $timeLog->start_time = now();
            $timeLog->end_time = now();
            $timeLog->client_project_time = $request->time;
            $timeLog->memo = $request->note;
            $timeLog->billable = $request->billable;
            $timeLog->hourly_rate = 0;
            $timeLog->earnings = 0;
            $timeLog->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Your time log added successfully. Thanks'                
            );
            return Response()->json($data, $this->successStatus); 
            }
            else{
                $data = array(
                    "status" => true,
                    'message' => 'Time log id not found.'                
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
            }

    }
    public function createTimelog(){
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
                'articles' => $products,
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
    public function deleteTimelogs(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }
        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                $timeLog = ProjectTimeLog::findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'The time log has been deleted successfully'                
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
    public function startTimelogs(Request $request){
           /* $user = auth()->user()->user;
            $timeLog = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember')->where('company_id',$user->company_id)->where('added_by',$user->id)->where('id',$request->id)->first();
            $client_project_time = $timeLog->client_project_time;
            $arr = explode(":", $client_project_time);
            $hours = 'PT'.$arr[0].'H'.$arr[1].'M'.$arr[2].'S';
            $datetime1 = new DateTime();
            $datetime1->add(new DateInterval($hours));
            $datetime2 = $timeLog->start_time;
            $interval = $datetime1->diff($datetime2);
            return $timeTrack = $interval->format('%h:%i:%s'); */
        try{
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [ 
                    'id' => 'required'
            ]);
            if ($validator->fails()) { 
                return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $user = auth()->user()->user;
            $timeLog = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember')->where('company_id',$user->company_id)->where('added_by',$user->id)->where('id',$request->id)->first();
            if($timeLog){
            $timeLog->start_time = now();
            $timeLog->end_time = null;
            $timeLog->save();
            DB::commit();
            $client_project_time = $timeLog->client_project_time;
            $arr = explode(":", $client_project_time);
            $hours = 'PT'.$arr[0].'H'.$arr[1].'M'.$arr[2].'S';
            $datetime1 = new DateTime();
            $datetime1->add(new DateInterval($hours));
            $datetime2 = $timeLog->start_time;
            $interval = $datetime1->diff($datetime2);
            /* $timeTrack = $interval->format('%h:%i:%s'); */
            $timeTrack = ($interval->days * 24 + $interval->h) . ":" . $interval->i.":".$interval->s;
            $data = array(
                "status" => true,
                "tracking_time"=>$timeTrack,
                'message' => 'Your time log start successfully. Thanks'                
            );
            return Response()->json($data, $this->successStatus); 
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Time log id not found.'                
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
            }

    }
    public function discardTimelogs(Request $request){
     try{
         DB::beginTransaction();
         $validator = Validator::make($request->all(), [ 
                 'id' => 'required'
         ]);
         if ($validator->fails()) { 
             return response()->json(['message'=>$validator->errors()->first(),'status' => false], $this->successStatus);
         }
         $user = auth()->user()->user;
         $timeLog = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember')->where('company_id',$user->company_id)->where('added_by',$user->id)->where('id',$request->id)->first();
         if($timeLog){
         $timeLog->start_time = now();
         $timeLog->end_time = now();
         $timeLog->save();
         DB::commit();
         $data = array(
             "status" => true,
             'message' => 'Your time log discard successfully. Thanks'                
         );
         return Response()->json($data, $this->successStatus); 
         }
         else{
             $data = array(
                 "status" => false,
                 'message' => 'Time log id not found.'                
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
         }

 }
 public function runningTimelogs(Request $request){
    try{
        $user = auth()->user()->user;
        $timeLog = ProjectTimeLog::with('timeLogArticle','timeLogTeamMember')->where('company_id',$user->company_id)->where('added_by',$user->id)->whereNull('end_time')->first();
        if($timeLog){
            $client_project_time = $timeLog->client_project_time;
            $arr = explode(":", $client_project_time);
            $hours = 'PT'.$arr[0].'H'.$arr[1].'M'.$arr[2].'S';
            $datetime1 = new DateTime();
            $datetime1->add(new DateInterval($hours));
            $datetime2 = $timeLog->start_time;
            $interval = $datetime1->diff($datetime2);
            //$timeTrack = $interval->format('%h:%i:%s');
            //return ($interval->days) * 24 + $interval->h;
           $timeTrack = ($interval->days * 24 + $interval->h) . ":" . $interval->i.":".$interval->s;
            $data = array(
                "status" => true,
                'timeLogs' => $timeLog,
                "tracking_time"=>$timeTrack,
                
            );
            return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'There is no time log running!'
                    
                );
                return Response()->json($data, $this->forbiddenStatus);
            }
        }catch (ApiException $e) {
            $data = array(
                "status" => false,
                'message' => "Something went worng try later",
                'error' => $e->getMessage()
                
            );
            return Response()->json($data, $this->successStatus); 
        }

 }
}
