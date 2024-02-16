<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\VatTypes;
use App\Models\TaxAccountNumber;
use Illuminate\Http\Request;
use App\Models\VatPercentage;

class VatTypesController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.vatType';
        $this->activeSettingMenu = 'tax_settings';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('manage_tax') !== 'all');

            return $next($request);
        });
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        $this->vatType = VatTypes::get();
        $VatPercentage= VatPercentage::all();
        $this->accountCodeRate = TaxAccountNumber::with('vatPercentage','vatType')->get();
        return view('tax-settings.createVatType', $this->data)->with('VatPercentage', $VatPercentage);
        

    }

    /**
     * @param StoreTax $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(Request $request)
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $taxType = new VatTypes();
        $taxType->vat_type = $request->vatType;
        $taxType->vat_type_se = $request->vatTypeSe;
        /* $taxType->vat_percentage = $request->vatRate; */
        $taxType->vat_percentage_id = $request->vat_percentage_id;
        $taxType->status = $request->status;
        $taxType->save();

        return Reply::successWithData(__('messages.recordSaved'), ['data' => strtoupper($taxType)]);

    }
    public function destroy($id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        VatTypes::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }
}
