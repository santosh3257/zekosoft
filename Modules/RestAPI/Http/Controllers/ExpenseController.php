<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Entities\Expense;
use Modules\RestAPI\Http\Requests\Expense\CreateRequest;
use Modules\RestAPI\Http\Requests\Expense\DeleteRequest;
use Modules\RestAPI\Http\Requests\Expense\IndexRequest;
use Modules\RestAPI\Http\Requests\Expense\ShowRequest;
use Modules\RestAPI\Http\Requests\Expense\UpdateRequest;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Helper\Files;
use Validator;
use App\Imports\ImportExpenses;
use App\Traits\ImportExcel;
use App\Exports\ExportExpenses;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends ApiBaseController
{
    use ImportExcel;
    protected $model = Expense::class;

    protected $indexRequest = IndexRequest::class;

    protected $storeRequest = CreateRequest::class;

    protected $updateRequest = UpdateRequest::class;

    protected $showRequest = ShowRequest::class;

    protected $deleteRequest = DeleteRequest::class;

    public function modifyIndex($query)
    {
        return $query->visibility()
            ->join(
                \DB::raw('(SELECT `id` as `a_user_id`, `name` as `employee_name` FROM `users`) as `a`'),
                'a.a_user_id',
                '=',
                'expenses.user_id'
            );
    }
    public function allExpenses(Request $request){
        $user = auth()->user()->user;
        $query = Expense::with('clientInfo')->where('added_by',$user->id);
        if(!empty($request->search)){
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                    $query->whereHas('clientInfo', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
                })->orWhere('item_name', 'LIKE', '%'.$request->search.'%');
        }
        if(!empty($request->name)){
            $query->where('item_name', 'LIKE', '%'.$request->name.'%');
        }
        if(!empty($request->category_id)){
            $query->where('category_id',$request->category_id);
        }
        if(!empty($request->project_id)){
            $query->where('project_id', $request->project_id);
        }
        $expense = $query->orderBy('id', 'DESC')->paginate($request->per_page);
        $data = array(
            "status" => true,
            'expense' => $expense
            
        );
        return Response()->json($data, $this->successStatus);
    }
    public function addExpenses(Request $request){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $validator = Validator::make($request->all(), [ 
                'item_name' => 'required',
                'expenses_date' => 'required',
                "amount" => 'required',
                "vat_amount" => 'required',
                'mode_of_payment' => "required",
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $expenses = new Expense();
            $expenses->company_id = $user->company_id;
            $expenses->item_name = $request->item_name;
            $expenses->purchase_date = $request->expenses_date;
            $expenses->price = $request->amount;
            if(isset($request->currency_id)){
                $expenses->currency_id = $request->currency_id;  
            }
            else{
                $expenses->currency_id = 317;
            }
            $names = [];
            if ($request->hasFile('bill_attachment')) {
                    Files::createDirectoryIfNotExist('expenses/attachments');
                    //$imageName = Files::uploadLocalOrS3($request->bill_attachment, 'expenses/attachments/', 300);
                    
                    foreach($request->file('bill_attachment') as $key=>$image)
                    {
                        $imageName = Files::uploadLocalOrS3($image, 'expenses/attachments/', 300);
                        array_push($names, $imageName);          

                    }
                }
            $expenses->bill = json_encode($names);
            $expenses->user_id = $user->id;
            $expenses->description = $request->description;
            $expenses->added_by = $user->id;
            $expenses->last_updated_by = $user->id;
            $expenses->approver_id = $user->id;
            $expenses->total_amount = $request->total_amount;
            $expenses->sub_total = $request->sub_total;
            $expenses->vat_amount = $request->vat_amount;
            $expenses->mode_of_payment = $request->mode_of_payment;
            $expenses->account_code_id = $request->account_code_id;
            if(isset($request->language_id)){
                $expenses->language_id = $request->language_id;  
            }
            else{
                $expenses->language_id = 1;
            }
            $expenses->assign_to = $request->assign_to;
            $expenses->assign_to_id = $request->assign_to_id;
            $expenses->status = 'approved';
            $expenses->save();
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Your Expenses created successfully',
                
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
    public function editExpenses(Request $request, $id){
        $user = auth()->user()->user;
        $expenseData = Expense::where('added_by',$user->id)->where('id',$id)->first();
        $expenses_images = json_decode($expenseData->bill);
        $expenseData->bill = $expenses_images;
        if($expenseData){

        $data = array(
            "status" => true,
            'expense' => $expenseData
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Expenses not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    public function updateExpenses(Request $request, $id){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $validator = Validator::make($request->all(), [ 
                'item_name' => 'required',
                'expenses_date' => 'required',
                "amount" => 'required',
                "vat_amount" => 'required',
                'mode_of_payment' => "required",
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $getExpenses = Expense::findOrFail($id);
            if($getExpenses){
                $getExpenses->company_id = $user->company_id;
                $getExpenses->item_name = $request->item_name;
                $getExpenses->purchase_date = $request->expenses_date;
                $getExpenses->price = $request->amount;
                if(isset($request->currency_id)){
                    $getExpenses->currency_id = $request->currency_id;
                }
                else{
                    $getExpenses->currency_id = 317;
                }
                if ($request->hasFile('bill_attachment')) {
                    $names = [];
                    Files::createDirectoryIfNotExist('expenses/attachments');
                    /* $imageName = Files::uploadLocalOrS3($request->bill_attachment, 'expenses/attachments/', 300); */
                    foreach($request->file('bill_attachment') as $key=>$image)
                    {
                        $imageName = Files::uploadLocalOrS3($image, 'expenses/attachments/', 300);
                        array_push($names, $imageName);          

                    }
                    $getExpenses->bill = json_encode($names);
                }
                else{
                    $getExpenses->bill =  $getExpenses->bill;
                }
                $getExpenses->user_id = $user->id;
                $getExpenses->description = $request->description;
                $getExpenses->added_by = $user->id;
                $getExpenses->last_updated_by = $user->id;
                $getExpenses->approver_id = $user->id;
                $getExpenses->total_amount = $request->total_amount;
                $getExpenses->sub_total = $request->sub_total;
                $getExpenses->vat_amount = $request->vat_amount;
                $getExpenses->mode_of_payment = $request->mode_of_payment;
                $getExpenses->account_code_id = $request->account_code_id;
                if(isset($request->language_id)){
                    $getExpenses->language_id = $request->language_id;  
                }
                else{
                    $getExpenses->language_id = 1;
                }
                $getExpenses->assign_to = $request->assign_to;
                $getExpenses->assign_to_id = $request->assign_to_id;
                $getExpenses->status = 'approved';
                $getExpenses->save();
                DB::commit();
                $data = array(
                    "status" => true,
                    'message' => 'Your Expenses update successfully'
                    
                );
                return Response()->json($data, $this->successStatus);
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => 'Expenses not found'
                    
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
    public function viewExpenses(Request $request, $id){
        $user = auth()->user()->user;
        $expense = Expense::where('added_by',$user->id)->where('id',$id)->first();
        $expenses_images = json_decode($expense->bill);
        $expense->bill = $expenses_images;
        if($expense){

        $data = array(
            "status" => true,
            'expense' => $expense
            
        );
        return Response()->json($data, $this->successStatus);
        }
        else{
            $data = array(
                "status" => false,
                'message' => 'Vendor bill not found'
                
            );
            return Response()->json($data, $this->forbiddenStatus);
        }

    }
    public function deleteExpenses(Request $request){
        $user = auth()->user()->user;
        $validator = Validator::make($request->all(), [ 
            'delete' => 'required|array',
        ]);

        if ($validator->fails()) { 
            return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
        }

        if(count($request->delete) > 0){
            foreach($request->delete as $key=>$id){
                Expense::where('added_by',$user->id)->findOrFail($id)->delete();
            }

            $data = array(
                "status" => true,
                'message' => 'Your expenses has been deleted successfully'                
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
    // Download Expenses file
    public function getExpensesFile(){
        $user = auth()->user()->user;
        $fileName = 'download/myexpenses-'.$user->company_id.'.csv';
        Excel::store(new ExportExpenses, $fileName, 'public');
        $url = url('storage/'.$fileName);
        $data = array(
            "status" => true,
            'url' => $url
            
        );
        return Response()->json($data, $this->successStatus); 
    }
     //function for import vendors
     
     public function createExpensesFile(Request $request){
        try{
            $validator = Validator::make($request->all(), [ 
                'import_file' => 'required|file|mimes:xls,xlsx,csv,txt'
            ]);
    
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            DB::beginTransaction();
            $user = auth()->user()->user;
            $columsName = $this->importFileJobProcess($request, ImportExpenses::class);
            if(!empty($columsName)){
                $columns = $columsName[0];
                $requiredColumns = array('Item Name','Date','Price','Currency Id','Status','Total Amount','Sub Amount','Tax Amount','Tax Id','Account Code Id','Language Id','Assign To','Assign To Id');
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
                        $expenses = new Expense();
                        $expenses->company_id = $user->company_id;
                        $expenses->item_name = $column_value['item_name'];
                        $expenses->purchase_date = $column_value['date'];
                        $expenses->price = $column_value['price'];
                        $expenses->currency_id = $column_value['currency_id'];
                        $expenses->user_id = $user->id;
                        $expenses->added_by = $user->id;
                        $expenses->last_updated_by = $user->id;
                        $expenses->approver_id = $user->id;
                        $expenses->total_amount = $column_value['total_amount'];
                        $expenses->sub_total = $column_value['sub_amount'];
                        $expenses->tax_amount = $column_value['tax_amount'];
                        $expenses->tax_id = $column_value['tax_id'];
                        $expenses->account_code_id = $column_value['account_code_id'];
                        $expenses->language_id = $column_value['language_id'];
                        $expenses->assign_to = $column_value['assign_to'];
                        $expenses->assign_to_id = $column_value['assign_to_id'];
                        $expenses->status = 'approved';
                        $expenses->save();
                        
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
    public function allExpensesCategory(Request $request){
        $user = auth()->user()->user;
        $expensesCategory = DB::table('expenses_category')->where('company_id',$user->company_id)->get();
        $data = array(
            "status" => true,
            'expensesCategory' => $expensesCategory
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function allAccountCode(Request $request){
        $user = auth()->user()->user;
        $accountCodes = DB::table('tax_account_numbers')->get();
        $data = array(
            "status" => true,
            'accountCodes' => $accountCodes
            
        );
        return Response()->json($data, $this->successStatus);

    }
    public function addExpensesCategory(Request $request){
        try{
            DB::beginTransaction();
            $user = auth()->user()->user;
            $values = array('company_id' => $user->company_id,'category_name' => $request->category_name,'created_at' => now(),'updated_at' => now());
            DB::table('expenses_category')->insert($values);
            DB::commit();
            $data = array(
                "status" => true,
                'message' => 'Your Expenses Category created successfully',
                
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
