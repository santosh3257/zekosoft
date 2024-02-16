<?php

namespace App\Http\Controllers;


use App\Helper\Reply;
use Illuminate\Http\Request;
use App\Models\Tax;
use App\Models\TaxAccountNumber;
use App\Models\VatPercentage;
use App\Models\VatTypes;
use App\Http\Requests\TaxAccountNumber\StoreTaxAccountNumber;
use App\Http\Requests\TaxAccountNumber\UpdateTaxAccountNumber;

class TaxAccountNumberController extends AccountBaseController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.taxAccountNumber';
        $this->activeSettingMenu = 'account_number';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('manage_account_number') !== 'all');

            return $next($request);
        });
    }

    public function index()
    {
        $this->taxAccountNumbers= TaxAccountNumber::with('vatPercentage')->get();
        $keyword = isset($_GET['tax_id'])? $_GET['tax_id'] : '';
        $taxNumber= TaxAccountNumber::with('vatPercentage');
        if((isset($_GET['tax_id'])) && (!empty($_GET['tax_id']))){
            $taxNumber->Where(function ($q) use ($keyword) {
                $q->where('vat_percentage_id', $keyword)
                ;});
        }
        $taxNumbers = $taxNumber->get();
        $VatPercentages= VatPercentage::all();
        /* print_r($this->data);
        die();  */
        return view('tax-account-number.index',$this->data)->with('VatPercentages', $VatPercentages)->with('taxNumbers', $taxNumbers);
    }

    public function create()
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        $this->vatTypes = VatTypes::get();
        $this->percentages = VatPercentage::get();
        // via is extra parameter sent from tax-settings to know if this request comes from tax-settings or product-create-edit page
        if (request()->via && request()->via == 'tax-account-number') {
            return view('tax-account-number.create', $this->data);
        }

        

        return view('tax-account-number.create', $this->data);

    }

    /**
     * @param StoreTaxAccountNumber $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreTaxAccountNumber $request)
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $accountNumber = new TaxAccountNumber();
        $accountNumber->account_number = $request->account_number;
        $accountNumber->vat_percentage_id = $request->tax_id;
        $accountNumber->status = $request->status;
        if($request->defaultCodes){
            /* $accountNumber->default_checked = 1; */
            $accountNumber->set_default = 'yes';
        }
        else{
            /* $accountNumber->default_checked = 0; */
            $accountNumber->set_default = 'no';
        }
        $accountNumber->description = $request->description;
        $accountNumber->description_se = $request->description_se;
        $accountNumber->save();
        $check = TaxAccountNumber::where('set_default','yes')->where('vat_percentage_id',$accountNumber->vat_percentage_id)->first();
        if($check){
            if($check->account_number != $accountNumber->account_number){
                $data = TaxAccountNumber::where('account_number',$accountNumber->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'yes']);
                $data = TaxAccountNumber::where('account_number',$check->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'no']);

            }

        }
        else{
            $data = TaxAccountNumber::where('account_number',$accountNumber->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'yes']);
        }


        return Reply::successWithData(__('messages.recordSaved'), ['data' => strtoupper($accountNumber)]);

    }

    public function edit($id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        $this->vatTypes = VatTypes::get();
        $this->percentages = VatPercentage::get();
        $this->taxAccountCode = TaxAccountNumber::findOrFail($id);
        return view('tax-account-number.edit', $this->data);
    }

    /**
     * @param UpdateTaxAccountNumber $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(UpdateTaxAccountNumber $request, $id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $accountNumber = TaxAccountNumber::findOrFail($id);
        $accountNumber->account_number = $request->account_number;
        $accountNumber->vat_percentage_id = $request->tax_id;
        $accountNumber->status = $request->status;
        if($request->defaultCodes){
            /* $accountNumber->default_checked = 1; */
            $check = TaxAccountNumber::where('set_default','yes')->where('vat_percentage_id',$accountNumber->vat_percentage_id)->first();
            if($check){
                if($check->account_number != $accountNumber->account_number){
                    $data = TaxAccountNumber::where('account_number',$accountNumber->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'yes']);
                    $data = TaxAccountNumber::where('account_number',$check->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'no']);

                }

            }
            else{
                $data = TaxAccountNumber::where('account_number',$accountNumber->account_number)->where('vat_percentage_id',$accountNumber->vat_percentage_id)->update(['set_default'=>'yes']);
            }
        }
        else{
            $accountNumber->set_default = 'no';
            /* $accountNumber->default_checked = 0; */
        }
        $accountNumber->description = $request->description;
        $accountNumber->description_se = $request->description_se;
        $accountNumber->save();

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $accountNumber]);

    }
    public function updateDefaultAccountCode(Request $request){
        abort_403(user()->permission('manage_tax') !== 'all');
        $check = TaxAccountNumber::where('set_default','yes')->where('vat_percentage_id',$request->percentage_id)->first();
        if($check){
            if($check->account_number != $request->account_id){
                $data = TaxAccountNumber::where('account_number',$request->account_id)->where('vat_percentage_id',$request->percentage_id)->update(['set_default'=>'yes']);
                $data = TaxAccountNumber::where('account_number',$check->account_number)->where('vat_percentage_id',$request->percentage_id)->update(['set_default'=>'no']);

            }

        }
        else{
            $data = TaxAccountNumber::where('account_number',$request->account_id)->where('vat_percentage_id',$request->percentage_id)->update(['set_default'=>'yes']);
        }
        
        return response()->json(array('success'=>true,'message' => 'account code status change successfully.'));
        
        //dd($request->account_id);

    }
    public function updateDefaultAccountCodeNo(Request $request){
        abort_403(user()->permission('manage_tax') !== 'all');
            $data = TaxAccountNumber::where('account_number',$request->account_id)->where('vat_percentage_id',$request->percentage_id)->update(['set_default'=>'no']);
        
        
        return response()->json(array('success'=>true,'message' => 'account code status successfully.'));
        //dd($request->account_id);

    }
    public function destroy($id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        TaxAccountNumber::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }
}
