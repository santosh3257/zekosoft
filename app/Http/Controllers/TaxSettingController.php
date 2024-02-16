<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\Tax\StoreTax;
use App\Http\Requests\Tax\UpdateTax;
use App\Models\Tax;
use App\Models\VatTypes;
use App\Models\TaxAccountNumber;
use Illuminate\Http\Request;
use App\Models\VatPercentage;

class TaxSettingController extends AccountBaseController
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

    public function index()
    {
        $this->taxes = VatTypes::with('vatPercentage')->get();
        // dd($this->taxes);
        // die();
        return view('tax-settings.index', $this->data);
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        $this->vatType = VatTypes::get();
        // via is extra parameter sent from tax-settings to know if this request comes from tax-settings or product-create-edit page
        if (request()->via && request()->via == 'tax-setting') {
            return view('tax-settings.create', $this->data);
        }
        if (request()->via && request()->via == 'tax-vat-type-setting') {
            return view('tax-settings.createVatType', $this->data);
        }
        
        $this->taxes = Tax::get();

        return view('tax.create', $this->data);

    }

    public function edit($id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        $this->tax = VatTypes::findOrFail($id);
        $this->vatPercentage = VatPercentage::all();
        return view('tax-settings.edit', $this->data);
    }

    /**
     * @param StoreTax $request
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(StoreTax $request)
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $tax = new Tax();
        $tax->tax_name = $request->tax_name;
        $tax->rate_percent = $request->rate_percent;
        $tax->tax_type = $request->vat_type_id;
        $tax->save();

        $taxes = $this->taxDropdown();

        return Reply::successWithData(__('messages.recordSaved'), ['data' => strtoupper($taxes)]);

    }

    /**
     * @param UpdateTax $request
     * @param int $id
     * @return array
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function update(Request $request, $id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $taxType = VatTypes::find($id);
        if($taxType){
        $taxType->vat_type = $request->vatType;
        $taxType->vat_type_se = $request->vatTypeSe;
        $taxType->vat_percentage_id = $request->vat_percentage_id;
        $taxType->status = $request->status;
        $taxType->save();
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['data' => $taxType]);

    }

    public function taxDropdown()
    {
        abort_403(user()->permission('manage_tax') !== 'all');

        $taxes = Tax::get();
        $taxOptions = '<option value="">--</option>';

        foreach ($taxes as $item) {
            $taxOptions .= '<option  value="' . $item->id . '">' . $item->tax_name . ' : ' . $item->rate_percent . '</option>';
        }

        return $taxOptions;
    }

    public function destroy($id)
    {
        abort_403(user()->permission('manage_tax') !== 'all');
        Tax::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

}
