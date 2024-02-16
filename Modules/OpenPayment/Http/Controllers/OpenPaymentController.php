<?php

namespace Modules\OpenPayment\Http\Controllers;
use Auth;
use stdClass;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\OpenPayment\Entities\OpenBankingToken;
use Modules\RestAPI\Http\Controllers\ApiBaseController;
use Modules\OpenPayment\Entities\OpenpaymentUserAccount;

class OpenPaymentController extends ApiBaseController
{
    protected $authUrl = '';
    protected $apiUrl = '';
    protected $clientId = '';
    protected $clientSecret = '';
    protected $aspspinformationScope = '';
    protected $accountinformationScope = '';
    protected $psuCorporateId = '';
    protected $psuId = '';
    protected $passPhrase = '';

    protected $paymentScope = '';

    protected $cert = '';
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
        $this->passPhrase = env('OPENPAYMENT_PEMPASSPRAHSE');
        $this->cert = base_path()."/Zekosoft_AB_PEM.pem";
        if(env('OPENPAYMENT_LIVE_ENV')){
            $this->certificate['cert'] = [$this->cert, $this->passPhrase];
        }
    }
    public function getAspspInformation(Request $req){
        try{

            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','bank_details_token')->first();
            if($token && $token->token){
                    $data = $this->fetchAspspsList($token->token);
                    return Response()->json(["success" => true,"data" => $data], 200);
            }else{ 
                OpenBankingToken::where('token_for','bank_details_token')->delete();
                $connectedToken =  $this->createConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    OpenBankingToken::create(['token' => $connectedToken['access_token']]);
                    $data = $this->fetchAspspsList($connectedToken['access_token']);
                    return Response()->json(["success" => true,"data" => $data], 200);
                }
            }
        }
        catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getAspspDetails(Request $req,$bicFi){
        try{

            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','bank_details_token')->first();
            if($token && $token->token){
                    $data = $this->fetchAspspDetails($token->token,$bicFi);
                    return Response()->json(["success" => true,"data" => $data], 200);
            }else{ 
                OpenBankingToken::where('token_for','bank_details_token')->delete();
                $connectedToken =  $this->createConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    OpenBankingToken::create(['token' => $connectedToken['access_token']]);
                    $data = $this->fetchAspspDetails($connectedToken['access_token'],$bicFi);
                    return Response()->json(["success" => true,"data" => $data], 200);
                }
            }
        }
        catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /*
    * Create Consent 
    * and Start Consent Authorisation
    */
    public function createConsent(Request $req,$bicFi){

        try{
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','account_information_token')->delete();
                $connectedToken =  $this->createAccountPrivateConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
                }
            }
            
            if($token){
                $data = $this->initiateCreateConsent($token,$bicFi);
                if($data){
                    $startConsentResponse = $this->startConsentAuthorisationProcess($token,$bicFi,$data['consentId']);
                    return Response()->json(["success" => true,"data" => $startConsentResponse,"start_consent_process"=>true,'consent_id'=>$data['consentId'],'authorisation_id'=>$startConsentResponse['authorisationId']], 200);
                }else{
                    return Response()->json(["success" => true,"data" => $data,"create_consent" => false], 200);
                }
            }
            
        }
        catch (\Throwable $th) {
            Log::info($th);
        }
    }

    /*
    * Update PSU Data
    */
    public function updatePsuDataRequest(Request $request){

        try{
            
            $validator = Validator::make($request->all(), [
                'consent_id' => 'required',
                'authorisation_id' => 'required',
                'bicfi' => 'required',
                'authentication_method_id' => 'required'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }
            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','account_information_token')->delete();
                $connectedToken =  $this->createAccountPrivateConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
                }
            }
            
            if($token){
                $input = $request->all();
                
                $psuResponse = $this->updatePsuData($token,$input['bicfi'],$input['consent_id'],$input['authorisation_id'],$input['authentication_method_id']);
                return Response()->json(["success" => true,"data" => $psuResponse,'consent_data'=>$input], 200);
            }
            
        }
        catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getConsentAuthorisationSCAStatusRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'consent_id' => 'required',
            'authorisation_id' => 'required',
            'bicfi' => 'required'
        ]);

        if ($validator->fails()) {
            return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
    
        }
        $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
        if($token){
            $token = $token->token;
        }else{
            OpenBankingToken::where('token_for','account_information_token')->delete();
            $connectedToken =  $this->createAccountPrivateConnectToken();
            if($connectedToken && array_key_exists('access_token', $connectedToken)){
                $token = $connectedToken['access_token'];
                OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
            }
        }
        
        if($token){
            $input = $request->all();
            
            $psuResponse = $this->getConsentAuthorisationSCAStatus($token,$input['bicfi'],$input['consent_id'],$input['authorisation_id']);
            return Response()->json(["success" => true,"data" => $psuResponse,], 200);
        }
    }

    public function getConsentStatusRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'consent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
    
        }
        $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
        if($token){
            $token = $token->token;
        }else{
            OpenBankingToken::where('token_for','account_information_token')->delete();
            $connectedToken =  $this->createAccountPrivateConnectToken();
            if($connectedToken && array_key_exists('access_token', $connectedToken)){
                $token = $connectedToken['access_token'];
                OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
            }
        }
        
        if($token){
            $input = $request->all();
            
            $consentStatus = $this->getConsentStatus($token,$input['bicfi'],$input['consent_id']);
            if($consentStatus && array_key_exists("consentStatus",$consentStatus) && $consentStatus['consentStatus'] == 'valid'){
                
                //Save consent id to get account information
                $consent = OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi'=>$input['bicfi']])->first();
                if($consent){
                    $accountList = $this->getAccountList($token,$input['consent_id'],$input['bicfi']);
                    if($accountList && array_key_exists('accounts',$accountList)){
                        OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi'=>$input['bicfi']])
                        ->update(['user_id' => Auth::user()->id,'accounts' => json_encode($accountList['accounts']),'consent_id'=>$input['consent_id'],'bicfi'=>$input['bicfi'],'iban' => $accountList['accounts'][0]['iban'],'bban' => $accountList['accounts'][0]['bban']]);
                    }
                }else{
                    $accountList = $this->getAccountList($token,$input['consent_id'],$input['bicfi']);
                    if($accountList && array_key_exists('accounts',$accountList)){
                        OpenpaymentUserAccount::create(['user_id' => Auth::user()->id,'iban' => $accountList['accounts'][0]['iban'],'bban' => $accountList['accounts'][0]['bban'],'accounts' => json_encode($accountList['accounts']),'consent_id'=>$input['consent_id'],'bicfi'=>$input['bicfi']]);
                    }
                }
            }

            return Response()->json(["success" => true,"data" => $consentStatus,], 200);
        }
    }

    /**
     * Get Account List
     */
    public function getAccountListRequest(Request $request){
        try{
            
            $accountList = OpenpaymentUserAccount::where('user_id',Auth::user()->id)->get();
            return Response()->json(["success" => true,"data" => $accountList], 200);

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getAccountDetailsRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'bicfi' => 'required',
                'resource_id' => 'required'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','account_information_token')->delete();
                $connectedToken =  $this->createAccountPrivateConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
                }
            }
            
            if($token){
                $consent = OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi' => $request->bicfi])->first();
                if($consent){
                    $accountLists = $this->getAccountDetails($token,$consent->consent_id,$consent->bicfi,$request->resource_id);
                    return Response()->json(["success" => true,"data" => $accountLists], 200);
                }

            }
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getTransactionListRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'bicfi' => 'required',
                'resource_id' => 'required',
                'from_date' => 'required|date',
                'to_date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','account_information_token')->delete();
                $connectedToken =  $this->createAccountPrivateConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
                }
            }
            
            if($token){
                $consent = OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi' => $request->bicfi])->first();
                if($consent){

                    $transactionList = $this->getTransactionList($token,$consent->consent_id,$consent->bicfi,$request->resource_id,$request->from_date,$request->to_date);
                    return Response()->json(["success" => true,"data" => $transactionList], 200);
                }

            }
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getTransactionDetailsRequest(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'bicfi' => 'required',
                'resource_id' => 'required',
                'transaction_id' => 'required',
            ]);

            if ($validator->fails()) {
                return Response()->json(["success" => false,"message" => $validator->messages()->first()], 403);
        
            }

            $token = OpenBankingToken::where('created_at', '>=', Carbon::now()->subHours(1)->toDateTimeString())->where('token_for','account_information_token')->first();
            if($token){
                $token = $token->token;
            }else{
                OpenBankingToken::where('token_for','account_information_token')->delete();
                $connectedToken =  $this->createAccountPrivateConnectToken();
                if($connectedToken && array_key_exists('access_token', $connectedToken)){
                    $token = $connectedToken['access_token'];
                    OpenBankingToken::create(['token' => $token,'token_for'=>'account_information_token']);
                }
            }
            
            if($token){
                $consent = OpenpaymentUserAccount::where(['user_id' => Auth::user()->id,'bicfi' => $request->bicfi])->first();
                if($consent){

                    $transactionList = $this->getTransactionDetails($token,$consent->consent_id,$consent->bicfi,$request->resource_id,$request->transaction_id);
                    return Response()->json(["success" => true,"data" => $transactionList], 200);
                }

            }
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    
    public function createConnectToken(){
        try{
            $client = new Client();
            $url = $this->authUrl."/connect/token";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/x-www-form-urlencoded', 
                     'TPP-Redirect-Preferred' => 'false' 
                ],
                'form_params' => [
                     'client_id' => $this->clientId,
                     'client_secret' => $this->clientSecret, 
                     'grant_type' => 'client_credentials',
                     'scope'  => $this->aspspinformationScope
                ],
                $this->certificate    
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

    public function createAccountPrivateConnectToken(){
        try{
            $client = new Client();
            $url = $this->authUrl."/connect/token";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/x-www-form-urlencoded',
                     'TPP-Redirect-Preferred' => 'false' 
                ],
                'form_params' => [
                     'client_id' => $this->clientId,
                     'client_secret' => $this->clientSecret, 
                     'grant_type' => 'client_credentials',
                     'scope'  => $this->accountinformationScope,
                ] ,
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
    public function fetchAspspsList($token){
        try{
            
            $client = new Client();
            $url = $this->apiUrl."/psd2/aspspinformation/v1/aspsps";
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token
                ],
                'cert' => [$this->cert, $this->passPhrase]   
            ]);
            
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function fetchAspspDetails($token, $bicFi){
        try{
            
            $client = new Client();
            $url = $this->apiUrl."/psd2/aspspinformation/v1/aspsps/".$bicFi;
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                ],
                'cert' => [$this->cert, $this->passPhrase]   
            ]);
            
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function initiateCreateConsent($token, $bicFi){
        try{
            $body = [
                'access' => new stdClass(),
                'recurringIndicator' => true, 
                'validUntil' => Carbon::now()->addDays(4),
                'frequencyPerDay'  => 4,
                'combinedServiceIndicator' => false
            ];
            $client = new Client();
            $url = $this->apiUrl."/psd2/consent/v1/consents";
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
            dd($th);
        }
    }

    public function startConsentAuthorisationProcess($token, $bicFi, $consentId){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/consent/v1/consents/".$consentId."/authorisations";
            $response = $client->request('POST', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function updatePsuData($token, $bicFi, $consentId,$consentAuthorisationId,$authenticationMethodId){
        try{
            $body = [
                'authenticationMethodId' => $authenticationMethodId,
            ];
            $client = new Client();
            $url = $this->apiUrl."/psd2/consent/v1/consents/".$consentId."/authorisations/".$consentAuthorisationId;
            $response = $client->request('PUT', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                'body' => json_encode($body),
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getConsentAuthorisationSCAStatus($token, $bicFi, $consentId,$consentAuthorisationId){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/consent/v1/consents/".$consentId."/authorisations/".$consentAuthorisationId;
            
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getConsentStatus($token, $bicFi, $consentId){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/consent/v1/consents/".$consentId."/status";
            
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT']
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            $response = json_decode($response->getBody(), true);
            return $response;
            

        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getAccountList($token,$consentId,$bicFi){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/accountinformation/v1/accounts?withBalance=true";
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'Consent-ID' =>$consentId
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            $response = json_decode($response->getBody(), true);
            if($response){
                return $response;
            }else{
                return false;
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }

    public function getAccountDetails($token,$consentId,$bicFi,$accountId){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/accountinformation/v1/accounts/".$accountId."?withBalance=true";
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'Consent-ID' =>$consentId
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            $response = json_decode($response->getBody(), true);
            if($response){
                return $response;
            }else{
                return false;
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }
    public function getTransactionList($token,$consentId,$bicFi,$accountId,$dateFrom,$toDate){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/accountinformation/v1/accounts/".$accountId."/transactions?bookingStatus=both&dateFrom=".$dateFrom."&dateTo=".$toDate;
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'Consent-ID' =>$consentId
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            $response = json_decode($response->getBody(), true);
            if($response){
                return $response;
            }else{
                return false;
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
        }
    }
    public function getTransactionDetails($token,$consentId,$bicFi,$accountId,$transactionId){
        try{
            $client = new Client();
            $url = $this->apiUrl."/psd2/accountinformation/v1/accounts/".$accountId."/transactions/".$transactionId;
            $response = $client->request('GET', $url, [ 
                'headers' => [
                     'X-Request-ID' => $this->uuid(),
                     'Authorization' => 'Bearer '.$token,
                     'PSU-Corporate-Id' => $this->psuCorporateId,
                     'PSU-ID' => $this->psuId,
                     'TPP-Redirect-Preferred' => 'false',
                     'X-BicFi' => $bicFi,
                     'Accept' => 'application/json',
                     'Content-Type' => 'application/json',
                     'PSU-IP-Address' => $this->getClientIpAddress(),
                     'PSU-User-Agent' => $_SERVER['HTTP_USER_AGENT'],
                     'Consent-ID' =>$consentId
                ],
                'cert' => [$this->cert, $this->passPhrase]
            ]);
            
            $response = json_decode($response->getBody(), true);
            if($response){
                return $response;
            }else{
                return false;
            }
            
        }catch (\Throwable $th) {
            Log::info($th);
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