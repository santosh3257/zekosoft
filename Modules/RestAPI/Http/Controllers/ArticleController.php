<?php

namespace Modules\RestAPI\Http\Controllers;

use Modules\RestAPI\Http\Controllers\BaseController;
use Modules\RestAPI\Entities\Article;
use Validator;
use Illuminate\Http\Request;
use Froiden\RestAPI\ApiResponse;
use Modules\RestAPI\Http\Requests\Article\CreateArticleRequest;
use Modules\RestAPI\Http\Requests\Article\UpdateArticleRequest;
use Illuminate\Support\Facades\DB;

class ArticleController extends ApiBaseController {

    //Get My Articles
    public function myArticles(){
        $user = auth()->user()->user;
        $articles = Article::where('company_id',$user->company_id)->paginate(10);
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

            $article = new Article();
            $article->company_id = $user->company_id;
            $article->user_id = $user->id;
            $article->name = $request->name;
            $article->article_type = $request->article_type;
            $article->in_stock = $request->stock;
            $article->amount = $request->amount;
            $article->rate = $request->rate;
            $article->status = 'active';
            $article->note = $request->note;
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

    // Update article
    public function updateMyArticle(UpdateArticleRequest $request, $id){
        try{
            $article = Article::where('id',$id)->first();
            if($article){

                $article->name = $request->name;
                $article->article_type = $request->article_type;
                $article->in_stock = $request->stock;
                $article->amount = $request->amount;
                $article->rate = $request->rate;
                $article->note = $request->note;
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

}
