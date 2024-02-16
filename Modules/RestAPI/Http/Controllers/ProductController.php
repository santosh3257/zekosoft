<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Entities\Product;
use Modules\RestAPI\Http\Requests\Article\CreateArticleRequest;
use Modules\RestAPI\Http\Requests\Article\UpdateArticleRequest;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use App\Models\Tax;
use App\Models\VatPercentage;
use App\Models\TaxAccountNumber;
use Modules\RestAPI\Http\Requests\Article\ImportRequest;
use App\Imports\ImportArticle;
use App\Traits\ImportExcel;
use App\Exports\ExportArticles;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends ApiBaseController
{
    use ImportExcel;
    protected $model = Product::class;

        //Get My Articles
        public function myArticles(Request $request){
            $user = auth()->user()->user;
            $query = Product::where('company_id',$user->company_id)->whereIn('status',['active','inactive']);
            if(!empty($request->article_name)){
                $query->where('name', 'LIKE', '%'.$request->article_name.'%');
            }
            if(!empty($request->article_type)){
                $query->where('type', $request->article_type);
            }
            if(!empty($request->status)){
                $query->where('status', $request->status);
            }
            if(!empty($request->article_no)){
                $query->where('id', $request->article_no);
            }
            if(!empty($request->archive)){
                $query->where('archive', $request->archive);
            }
            else{
                $query->where('archive', 0);
            }
            $articles = $query->orderBy('id','desc')->paginate($request->per_page);
            $data = array(
                "status" => true,
                'articles' => $articles
                
            );
            return Response()->json($data, $this->successStatus);
        }
    
        // Add New Article
        public function addMyArticles(CreateArticleRequest $request){
            try{
                DB::beginTransaction();
                $user = auth()->user()->user;
    
                $article = new Product();
                $article->company_id = $user->company_id;
                $article->name = $request->name;
                $article->type = $request->type;
                $article->in_stock = $request->stock;
                $article->purchase_amount = $request->amount;
                $article->price = $request->rate;
                $article->taxes = $request->tax ? $request->tax : null;
                $article->account_code = $request->account_code ? $request->account_code : null;
                $article->status = $request->status;
                $article->description = $request->note;
                $article->save();
                DB::commit();
                $data = array(
                    "status" => true,
                    'message' => 'Article added successfully',
                    
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

        // Get Single Article
        public function getSingleArticles($id){
            $user = auth()->user()->user;
            $article = Product::where('company_id',$user->company_id)->where('id',$id)->first();
            $data = array(
                "status" => true,
                'article' => $article
                
            );
            return Response()->json($data, $this->successStatus);
        }
    
        // Update article
        public function updateMyArticle(UpdateArticleRequest $request, $id){
            try{
                $article = Product::where('id',$id)->first();
                if($article){
    
                    $article->name = $request->name;
                    $article->type = $request->type;
                    $article->in_stock = $request->stock;
                    $article->purchase_amount = $request->amount;
                    $article->price = $request->rate;
                    $article->taxes = $request->tax ? $request->tax : null;
                    $article->account_code = $request->account_code ? $request->account_code : null;
                    $article->status = $request->status;
                    $article->description = $request->note;
                    $article->save();
    
                    $data = array(
                        "status" => true,
                        'message' => 'Article update successfully'
                        
                    );
                    return Response()->json($data, $this->successStatus);
                }
                else{
                    $data = array(
                        "status" => false,
                        'message' => 'Article not found'
                        
                    );
                    return Response()->json($data, $this->forbiddenStatus);
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

        // Get My Company Tax
        public function getMyTaxRate(){
            $taxes = VatPercentage::with('accountCodes')->get();
            $data = array(
                "status" => true,
                'taxes' => $taxes
                
            );
            return Response()->json($data, $this->successStatus);

        }

        // Create Article via CSV/Excel file
        public function createArticlesFile(ImportRequest $request){
            try{
                DB::beginTransaction();
                $user = auth()->user()->user;
                $columsName = $this->importFileJobProcess($request, ImportArticle::class);
                if(!empty($columsName)){
                    $columns = $columsName[0];
                    $requiredColumns = array('Name','Type','Rate');
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
                        foreach($columnArray as $idx=>$column_value){

                            $article = new Product();
                            $article->company_id = $user->company_id;
                            $article->name = $column_value['name'];
                            $article->type = $column_value['type'];
                            if(isset($column_value['in_stock'])){
                                $article->in_stock = $column_value['in_stock'];
                            }
                            if(isset($column_value['purchase_amount'])){
                                $article->purchase_amount = $column_value['purchase_amount'];
                            }
                            $article->price = $column_value['rate'];
                            $article->taxes =  null;
                            $article->status = 'active';
                            if(isset($column_value['note'])){
                                $article->description = $column_value['note'];
                            }
                            $article->save();
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

        // Download Article file
        public function getArticlesFile(){
            $user = auth()->user()->user;
            $fileName = 'download/myarticles-'.$user->company_id.'.csv';
            Excel::store(new ExportArticles, $fileName, 'public');
            $url = url('storage/'.$fileName);
            $data = array(
                "status" => true,
                'url' => $url
                
            );
            return Response()->json($data, $this->successStatus); 
        }

        // Delete Article
        public function deleteArticles(Request $request){
            $validator = Validator::make($request->all(), [ 
                'id' => 'required|array',
                'id.*' => 'numeric',
            ]);
        
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }

            $user = auth()->user()->user;
            $articles  = Product::where('company_id',$user->company_id)->whereIn('id',$request->id)->delete();
            if($articles){
                $data = array(
                    "status" => true,
                    'message' => 'Article has been deleted successfully'
                    
                );
                return Response()->json($data, $this->successStatus);
            }else{
                $data = array(
                    "status" => false,
                    'message' => 'Article not found'
                    
                );
                return Response()->json($data, $this->forbiddenStatus);
            }
        }

        // Get all Account codes
        public function getTaxAccountCodes(Request $request){
            $query = TaxAccountNumber::where('status','active');
            if(!empty($request->account_number)){
                $query->where('account_number', 'LIKE', '%'.$request->account_number.'%');
            }
            $accountCodes = $query->orderBy('account_number','asc')->get();
            $data = array(
                "status" => true,
                'accountCodes' => $accountCodes
                
            );
            return Response()->json($data, $this->successStatus);
        }

        // Saved Article Status
        public function changeArticleStatus(Request $request, $article_id){
            $validator = Validator::make($request->all(), [ 
                'status' => 'required|in:active,inactive',
            ]);
        
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $article = Product::findOrFail($article_id);
            if($article){
                $article->status = $request->status;
                $article->save();
                
                $data = array(
                    "status" => true,
                    'message' => 'The status has been changed successfully. Thanks'                
                );
            
                return Response()->json($data, $this->successStatus); 
                
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "Article not found",
                    
                );
                return Response()->json($data, $this->successStatus);
            }
        }


        // Saved article with archive status
        public function changeArticleArchive(Request $request, $article_id){
            $validator = Validator::make($request->all(), [ 
                'archive' => 'required|in:0,1',
            ]);
        
            if ($validator->fails()) { 
                return response()->json(['errors'=>$validator->errors()->first(),'status' => false], $this->successStatus);
            }
            $article = Product::findOrFail($article_id);
            if($article){
                $article->archive = $request->archive;
                $article->save();
                if($request->archive == 1){
                    $data = array(
                        "status" => true,
                        'message' => 'The article has been archived successfully. Thanks'                
                    );
                }
                else{
                    $data = array(
                        "status" => true,
                        'message' => 'The article has been removed from arcived successfully. Thanks'                
                    );
                }
            
                return Response()->json($data, $this->successStatus); 
                
            }
            else{
                $data = array(
                    "status" => false,
                    'message' => "Article not found",
                    
                );
                return Response()->json($data, $this->successStatus);
            }
        }

        // Get All Active Articles
        public function getActiveArticles(){
            try{

                $user = auth()->user()->user;
                $products = Product::select('id','name','price','taxes','account_code','in_stock')->where('company_id',$user->company_id)->get();
                if($products){
                    foreach($products as $key=>$product){
                        $products[$key]->text = $product->name;
                    }
                }
                $data = array(
                    "status" => true,
                    'products' => $products
                    
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

}
