<?php

namespace App\Traits;

use Froiden\RestAPI\Exceptions\ApiException;
use Illuminate\Support\Facades\Config;

trait OpenPaymentTrait
{

    public function paymentStatus(){
        $bankwiseStatus = [];
        $bankwiseStatus['ESSESESS'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSC' => ['success' => true,'message' => 'payment success'],
            'ACTC' => ['success' => true,'message' => 'payment success'],
            'CANC' => ['success' => false,'message' => 'payment cancelled']
        ];
        $bankwiseStatus['SWEDSESS'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSC' => ['success' => true,'message' => 'payment success'],
            'CANC' => ['success' => false,'message' => 'payment cancelled'],
            'ACCP' => ['success'  => true, 'message' => 'payment success for future date']
        ];
        $bankwiseStatus['NDEASESS'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSC' => ['success' => true,'message' => 'payment success'],
            'CANC' => ['success' => false,'message' => 'payment cancelled'],
            'ACCP' => ['success'  => true, 'message' => 'payment success for future date']
        ];
        $bankwiseStatus['HANDSESS'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSC' => ['success' => true,'message' => 'payment success'],
            'CANC' => ['success' => false,'message' => 'payment cancelled'],
            'ACCP' => ['success'  => true, 'message' => 'payment success for future date']
        ];
        $bankwiseStatus['DABASESX'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSC' => ['success' => true,'message' => 'payment success'],
            'CANC' => ['success' => false,'message' => 'payment cancelled'],
        ];

        $bankwiseStatus['ELLFSESS'] = [
            'RJCT' => ['success' => false,'message' => 'payment failed'],
            'ACSP' => ['success' => true,'message' => 'payment success'],
            'ACTC' => ['success' => true,'message' => 'payment success for future date'],
            'CANC' => ['success' => false,'message' => 'payment cancelled'],
            'ACSC' => ['success' => true,'message' => 'payment success on creditor account'],
        ];
        
        return $bankwiseStatus;
    }

}
