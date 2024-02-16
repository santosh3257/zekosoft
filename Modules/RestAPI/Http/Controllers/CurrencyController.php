<?php

namespace Modules\RestAPI\Http\Controllers;

use App\Models\Currency;

class CurrencyController extends ApiBaseController
{
    protected $model = Currency::class;

    public function getCompanyCurrency(){
        $user = auth()->user()->user;
        $currency = Currency::where('company_id',$user->company_id)->get();
        $data = array(
            "status" => true,
            'currency' => $currency
            
        );
        return Response()->json($data, $this->successStatus);
    }
}
