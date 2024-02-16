<?php

namespace Modules\OpenPayment\Http\Controllers;
use Auth;
use stdClass;
use Carbon\Carbon;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\OpenPayment\Entities\OpenBankingToken;
use Modules\RestAPI\Http\Controllers\ApiBaseController;
use Modules\OpenPayment\Entities\OpenpaymentUserAccount;
use DB;
use App\Traits\OpenPaymentTrait;
class PaymentInitializeController extends ApiBaseController {

    use OpenPaymentTrait;
    protected $authUrl = '';
    protected $apiUrl = '';
    protected $clientId = '';
    protected $clientSecret = '';
    protected $aspspinformationScope = '';
    protected $accountinformationScope = '';
    protected $psuCorporateId = '';
    protected $psuId = '';

    protected $paymentScope = '';
    protected $passPhrase = '';
    
    protected $remittanceInformationUnstructured = 'My Payment';
    protected $cert='';
    protected $certificate = [];
    public function __construct(){
        $this->authUrl = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_AUTH_URL') : env('OPENPAYMENT_DEV_AUTH_URL');
        $this->apiUrl =  env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_API_URL') : env('OPENPAYMENT_DEV_API_URL');
        $this->clientId = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_CLIENT_ID') : env('OPENPAYMENT_DEV_CLIENT_ID');
        $this->clientSecret = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_CLIENT_SECRET') : env('OPENPAYMENT_DEV_CLIENT_SECRET');
        $this->aspspinformationScope = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_ASPSPINFORMATION_SCOPE') : env('OPENPAYMENT_ASPSPINFORMATION_SCOPE');
        $this->accountinformationScope = env('OPENPAYMENT_ACCOUNTINFORMATION_SCOPE');
        $this->psuCorporateId = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_PSU_CORPORATE_ID') : env('OPENPAYMENT_DEV_PSU_CORPORATE_ID');
        $this->psuId = env('OPENPAYMENT_LIVE_ENV') ? env('OPENPAYMENT_PSU_ID') : env('OPENPAYMENT_DEV_PSU_ID');
        $this->paymentScope = env('OPENPAYMENT_PAYMENT_SCOPE');
        if(env('REMITTANCE_INFORMATION_UNSTRUCTURED')){
            $this->remittanceInformationUnstructured = env('REMITTANCE_INFORMATION_UNSTRUCTURED');
        }
        $this->passPhrase = env('OPENPAYMENT_PEMPASSPRAHSE');
        $this->cert = base_path()."/Zekosoft_AB_PEM.pem";
        if(env('OPENPAYMENT_LIVE_ENV')){
            $this->certificate['cert'] = [$this->cert, $this->passPhrase];
        }
    }

    public function getAllUsers(){
        try{
            $users = DB::table('users')->select('name','email','id','user_auth_id')->where('status','active')->where('is_superadmin',0)->get();
            return Response()->json(["success" => true, "users" => $users], 200);
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }
    public function initializePaymentRequest(Request $request){

        try{
            $validator = Validator::make($request->all(), [
                'creditor_id' => 'required',
                'amount' => 'required',
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            //token Generate If not generated
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','paymentinitiation')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','paymentinitiation')->delete();
                $connectedToken =  $this->createPaymentInitiationToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'paymentinitiation']);
                }else{
                    return Response()->json(["success" => false,"message" => "token not generated"], 200);
                }
            }

            //Initialize Payment
            $debitorDetails = OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi'=>$request->bicfi])->first();
            $creditorDetails = OpenpaymentUserAccount::where(['user_id' => $request->creditor_id])->first();

            if(!$debitorDetails){
                return Response()->json(["success" => false,"data" => "Debitor details not available"], 200);
            }

            if(!$creditorDetails){
                return Response()->json(["success" => false,"data" => "Creditor details not available"], 200);
            }

            if($debitorDetails && $creditorDetails){
                $inializePayment = $this->initializePayment($token,$request->bicfi,$debitorDetails,$creditorDetails,$request->amount);

                if($inializePayment && is_array($inializePayment) && array_key_exists('exception',$inializePayment)){
                    return Response()->json(["success" => false,"message" => $inializePayment['message']], 200);
                }else{

                    if(is_array($inializePayment) && array_key_exists("paymentId",$inializePayment)){
                        
                        $startInitializePayment =  $this->startInitializePayment($token,$request->bicfi,$inializePayment['paymentId']);
                        return Response()->json(["success" => true,"data" => $startInitializePayment,"bicfi" => $request->bicfi,"payment_id"=>$inializePayment['paymentId']], 200);

                    }else{
                        
                        return Response()->json(["success" => false,"message" => "Something went wrong","data" => $inializePayment], 200);
                    }
                }
            }
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function startInitializePaymentRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required',
                'bicfi' => 'required',
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            //token Generate If not generated
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','paymentinitiation')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','paymentinitiation')->delete();
                $connectedToken =  $this->createPaymentInitiationToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'paymentinitiation']);
                }
            }

            //Initialize Payment
            $startInitializePayment =  $this->startInitializePayment($token,$request->bicfi,$request->payment_id);
            return Response()->json(["success" => true,"data" => $startInitializePayment,'bicfi' => $request->bicfi,"payment_id"=>$request->payment_id], 200);
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function updatePsuDataInitializePaymentRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required',
                'bicfi' => 'required',
                'authorization_id' => 'required',
                'authorization_method_id' => 'required'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            //token Generate If not generated
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','paymentinitiation')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','paymentinitiation')->delete();
                $connectedToken =  $this->createPaymentInitiationToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'paymentinitiation']);
                }
            }

            //Initialize Payment
            $startInitializePayment =  $this->updatePsuDataInitializePayment($token,$request->bicfi,$request->payment_id,$request->authorization_id,$request->authorization_method_id);
            return Response()->json(["success" => true,"data" => $startInitializePayment,'bicfi' => $request->bicfi,"payment_id"=>$request->payment_id,'authorization_id'=>$request->authorization_id], 200);
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getScaStatusRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required',
                'bicfi' => 'required',
                'authorization_id' => 'required'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            //token Generate If not generated
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','paymentinitiation')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','paymentinitiation')->delete();
                $connectedToken =  $this->createPaymentInitiationToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'paymentinitiation']);
                }
            }

            //Initialize Payment
            $startInitializePayment =  $this->getScaStatus($token,$request->bicfi,$request->payment_id,$request->authorization_id);
            return Response()->json(["success" => true,"data" => $startInitializePayment,'bicfi' => $request->bicfi,"payment_id"=>$request->payment_id,'authorization_id'=>$request->authorization_id], 200);
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getStatusRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required',
                'bicfi' => 'required',
                'authorization_id' => 'required'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            //token Generate If not generated
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','paymentinitiation')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','paymentinitiation')->delete();
                $connectedToken =  $this->createPaymentInitiationToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'paymentinitiation']);
                }
            }

            //Initialize Payment
            $checkingStatus =  $this->getStatus($token,$request->bicfi,$request->payment_id,$request->authorization_id);

            if(!empty($checkingStatus) && array_key_exists('transactionStatus',$checkingStatus)){
                $paymentStatus = $this->paymentStatus();
                if(!empty($paymentStatus) && array_key_exists($request->bicfi,$paymentStatus)){
                    $data = $paymentStatus[$request->bicfi][$checkingStatus['transactionStatus']];
                    $data['orignal_status'] = $checkingStatus;
                    return Response()->json($data, 200);
                }else{
                    return Response()->json(["success" => false,"data" => $checkingStatus], 200);
                }
            }else{
                return Response()->json(["success" => false,"data" => $checkingStatus], 200);
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /* 
     * Payment initiation Token  
     */
    public function createPaymentInitiationToken(){
        try{
            $client = new Client();
            $url = $this->authUrl."/connect/token";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/x-www-form-urlencoded', 
                ],
                'form_params' => [
                     'client_id' => $this->clientId,
                     'client_secret' => $this->clientSecret, 
                     'grant_type' => 'client_credentials',
                     'scope'  => $this->paymentScope
                ], 
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            
            $response = json_decode($response->getBody(), true);
            if(is_array($response) && array_key_exists('access_token', $response)){
                return $response;
            }else{
                return false;
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /* 
     * Payment initiation  
     */
    public function initializePayment($token, $bicFi, $debitorDetails, $creditorDetails,$amount){
        try{

            $instructedAmount = new stdClass();

            $instructedAmount->currency = "SEK";
            $instructedAmount->amount = $amount;

            $debtorAccount = new stdClass();
            $debtorAccount->iban = $debitorDetails->iban;
            $debtorAccount->currency = "SEK";

            $creditorAccount = new stdClass();
            $creditorAccount->iban = $creditorDetails->iban;
            $creditorAccount->currency = "SEK";

            $body = [
                'instructedAmount' => $instructedAmount,
                'debtorAccount' => $debtorAccount, 
                'creditorAccount' => $creditorAccount,
                'creditorName'  => "ibyte",
                'remittanceInformationUnstructured' => $this->remittanceInformationUnstructured,
//                'requestedExecutionDate' => '2024-01-08'
            ];

            $client = new Client();
            $url = $this->apiUrl."/psd2/paymentinitiation/v1/payments/domestic";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'TPP-Redirect-Preferred' => 'false',
                ],
                'body' => json_encode($body),
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
            //return ['success' => false,"message"=>$th->getMessage(),'exception' =>true];
        }
    }

    /* 
     * Start Payment initiation  
     */
    public function startInitializePayment($token, $bicFi, $paymentId){
        try{

            $client = new Client();
            $url = $this->apiUrl."/psd2/paymentinitiation/v1/payments/domestic/".$paymentId."/authorisations";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'TPP-Redirect-Preferred' => 'false',
                ],
                'cert' => [$this->cert, $this->passPhrase]   
            ]);

            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
            //return ['success' => false,"message"=>$th->getMessage(),'exception' =>true];
        }
    }

    public function updatePsuDataInitializePayment($token, $bicFi, $paymentId, $authorizationId, $authenticationMethodId){
        try{

            $client = new Client();
            $url = $this->apiUrl."/psd2/paymentinitiation/v1/payments/domestic/".$paymentId."/authorisations/".$authorizationId;
            $response = $client->request('PUT', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'TPP-Redirect-Preferred' => 'false',
                ],
                'body' => json_encode(['authenticationMethodId' => $authenticationMethodId]),
                'cert' => [$this->cert, $this->passPhrase]
            ]);

            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
            //return ['success' => false,"message"=>$th->getMessage(),'exception' =>true];
        }
    }

    public function getScaStatus($token, $bicFi, $paymentId, $authorizationId){
        try{

            $client = new Client();
            $url = $this->apiUrl."/psd2/paymentinitiation/v1/payments/domestic/".$paymentId."/authorisations/".$authorizationId;
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'TPP-Redirect-Preferred' => 'false',
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);

            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
            //return ['success' => false,"message"=>$th->getMessage(),'exception' =>true];
        }
    }

    public function getStatus($token, $bicFi, $paymentId, $authorizationId){
        try{

            $client = new Client();
            $url = $this->apiUrl."/psd2/paymentinitiation/v1/payments/domestic/".$paymentId."/status";
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'TPP-Redirect-Preferred' => 'false',
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);

            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
            //return ['success' => false,"message"=>$th->getMessage(),'exception' =>true];
        }
    }

    public function uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    function getClientIpAddress() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
