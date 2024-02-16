<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\Role;
use Modules\RestAPI\Entities\Client;
use Modules\RestAPI\Entities\User;
use Illuminate\Http\Request;
use Modules\RestAPI\Entities\Expense;

class ClientController extends ApiBaseController
{

       // Get My Clients
       public function getAllActiveClients(){
        try{

            $activeClients = User::allActiveClients();
            if($activeClients){
                foreach($activeClients as $key=>$client){
                    $activeClients[$key]->text = $client->name;
                }
            }
            $data = array(
                "status" => true,
                'clients' => $activeClients
                
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
    
    public function updating(Client $client)
    {
        $data = request()->all('client_detail')['client_detail'];
        $data['category_id'] = $data['category']['id'];
        $data['sub_category_id'] = $data['sub_category']['id'];
        unset($data['category']);
        unset($data['sub_category']);
        $client->client_details()->update($data);

        return $client;
    }
    public function allClientExpenses(Request $request,$id){
        $query = Expense::with('clientInfo')->where('assign_to_id',$id);
        if(!empty($request->search)){
            $query->where('item_name', 'LIKE', '%'.$request->search.'%');
        }
        $expense = $query->orderBy('id', 'DESC')->paginate($request->per_page);
        $data = array(
            "status" => true,
            'expense' => $expense
            
        );
        return Response()->json($data, $this->successStatus);
        
    }
}
