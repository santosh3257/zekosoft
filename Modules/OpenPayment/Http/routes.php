<?php

ApiRoute::group(['namespace' => 'Modules\OpenPayment\Http\Controllers', 'middleware' => ['auth:sanctum', 'api.auth']], function() {
    // Link Account Open Payments
    /***** Get ASPSP List *****/
    ApiRoute::get('get/aspspinformation',['uses' => 'OpenPaymentController@getAspspInformation']);
    ApiRoute::get('get/aspspdetails/{bicFi}',['uses' => 'OpenPaymentController@getAspspDetails']);
    ApiRoute::post('create/consent/{bicFi}',['uses' => 'OpenPaymentController@createConsent']);
    ApiRoute::post('update/psu/consent',['uses' => 'OpenPaymentController@updatePsuDataRequest']);
    ApiRoute::post('get/consent/authorisation/sca/status',['uses' => 'OpenPaymentController@getConsentAuthorisationSCAStatusRequest']);
    ApiRoute::post('get/consent/status',['uses' => 'OpenPaymentController@getConsentStatusRequest']);
    ApiRoute::get('get/bank/list',['uses' => 'OpenPaymentController@getBankList']);
    ApiRoute::post('get/account/list',['uses' => 'OpenPaymentController@getAccountListRequest']);
    ApiRoute::post('get/account/details',['uses' => 'OpenPaymentController@getAccountDetailsRequest']);
    ApiRoute::post('get/transaction/list',['uses' => 'OpenPaymentController@getTransactionListRequest']);
    ApiRoute::post('get/transaction/details',['uses' => 'OpenPaymentController@getTransactionDetailsRequest']);
    ApiRoute::post('initialize/payment',['uses' => 'PaymentInitializeController@initializePaymentRequest']);
    ApiRoute::post('start/initialize/payment',['uses' => 'PaymentInitializeController@startInitializePaymentRequest']);
    ApiRoute::post('update/psu/data/initialize/payment',['uses' => 'PaymentInitializeController@updatePsuDataInitializePaymentRequest']);
    ApiRoute::post('get/initialize/payment/sca/status',['uses' => 'PaymentInitializeController@getScaStatusRequest']);
    ApiRoute::post('get/initialize/payment/status',['uses' => 'PaymentInitializeController@getStatusRequest']);
    ApiRoute::get('get/all/users',['uses' => 'PaymentInitializeController@getAllUsers']);
});
