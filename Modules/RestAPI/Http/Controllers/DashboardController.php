<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\TaskboardColumn;
use Froiden\RestAPI\ApiResponse;
use Modules\RestAPI\Entities\Invoice;
use Modules\RestAPI\Entities\Project;
use Modules\RestAPI\Entities\Task;
use App\Models\Estimate;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends ApiBaseController
{
    public function dashboard()
    {
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);
        $user = auth()->user()->user;
        $taskBoardColumn = TaskboardColumn::all();

        $completedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'completed';
        })->first();

        $totalProjects = Project::select('projects.id')
            ->get()
            ->count();
        $totalInvoices = Invoice::where('company_id',$user->company_id)->get()->count();
        $previousWeek = Invoice::select('*')->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->get()->count();
         $currentWeek = Invoice::where('company_id',$user->company_id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->count();
        if($previousWeek > $currentWeek) {
            $difference = $currentWeek-$previousWeek;
            $percentage = $difference/$previousWeek*100;
        }
        else if($previousWeek == 0 && $currentWeek >0){
            $percentage = $currentWeek*100;
        }
        else if($previousWeek == 0 && $currentWeek == 0){
            $percentage = 0;
        }
        else{
            $difference = $currentWeek-$previousWeek;
            $percentage = $difference/$previousWeek*100;

        }
        $invoiceData = array(
        'count'=>$totalInvoices,
        'percentage'=>$percentage,
        );
        $totalEstimates = Estimate::where('company_id',$user->company_id)->get()->count();
        $previousWeekEstimates  = Estimate::select('*')->whereBetween('created_at', [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()])->get()->count();
         $currentWeekEstimates  = Estimate::where('company_id',$user->company_id)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get()->count();
        if($previousWeekEstimates > $currentWeekEstimates) {
            $difference = $currentWeekEstimates-$previousWeekEstimates;
            $percentage = $difference/$previousWeekEstimates*100;
        }
        else if($previousWeekEstimates == 0 && $currentWeekEstimates >0){
            $percentage = $currentWeekEstimates*100;
        }
        else if($previousWeekEstimates == 0 && $currentWeekEstimates == 0){
            $percentage = 0;
        }
        else{
            $difference = $currentWeekEstimates-$previousWeekEstimates;
            $percentage = $difference/$previousWeekEstimates*100;

        }
        $estimateData = array(
            'count'=>$totalEstimates,
            'percentage'=>$percentage,
            );
        $totalCustomers = User::customerCount();
        $previousWeekCustomers  = User::previousWeekCustomerCount();
        $currentWeekCustomer  = User::currentWeekCustomerCount();
        if($previousWeekCustomers > $currentWeekCustomer) {
            $difference = $currentWeekCustomer-$previousWeekCustomers;
            $percentage = $difference/$previousWeekCustomers*100;
        }
        else if($previousWeekCustomers == 0 && $currentWeekCustomer >0){
            $percentage = $currentWeekCustomer*100;
        }
        else if($previousWeekCustomers == 0 && $currentWeekCustomer == 0){
            $percentage = 0;
        }
        else{
            $difference = $currentWeekCustomer-$previousWeekCustomers;
            $percentage = $difference/$previousWeekCustomers*100;

        }
        $customerData = array(
            'count'=>$totalCustomers,
            'percentage'=>$percentage,
            );

        $pendingTasks = Task::select('tasks.id')
            ->where('board_column_id', '!=', $completedTaskColumn->id)
            ->get()
            ->count();

        $unpaidInvoices = Invoice::select('invoices.id')
            ->where('status', 'unpaid')
            ->get()
            ->count();

        /* return ApiResponse::make(null, [
            'unpaidInvoices' => $unpaidInvoices,
            'totalProjects' => $totalProjects,
            'pendingTasks' => $pendingTasks,
            'totalInvoices' => $invoiceData,
            'totalEstimates' => $estimateData,
            'totalCustomers' => $customerData,
        ]); */
        $data = array(
            'status' => true,
            'unpaidInvoices' => $unpaidInvoices,
            'totalProjects' => $totalProjects,
            'pendingTasks' => $pendingTasks,
            'totalInvoices' => $invoiceData,
            'totalEstimates' => $estimateData,
            'totalCustomers' => $customerData
            
        );
        return Response()->json($data, $this->successStatus);
    }

    public function myDashboard()
    {
        $taskBoardColumn = TaskboardColumn::all();

        $completedTaskColumn = $taskBoardColumn->filter(function ($value, $key) {
            return $value->slug == 'completed';
        })->first();

        $totalProjects = Project::select('projects.id')
            ->join('project_members', 'project_members.project_id', '=', 'projects.id')
            ->where('project_members.user_id', '=', api_user()->id)
            ->groupBy('projects.id')
            ->get()
            ->count();

        $pendingTasks = Task::select('tasks.id')
            ->where('board_column_id', '!=', $completedTaskColumn->id)
            ->join('task_users', 'task_users.task_id', '=', 'tasks.id')
            ->where('task_users.user_id', '=', api_user()->id)
            ->groupBy('tasks.id')
            ->get()
            ->count();

        $unpaidInvoices = Invoice::select('invoices.id')
            ->where('status', 'unpaid')
            ->get()
            ->count();

        return ApiResponse::make(null, [
            'unpaidInvoices' => $unpaidInvoices,
            'totalProjects' => $totalProjects,
            'pendingTasks' => $pendingTasks,
        ]);
    }
}
