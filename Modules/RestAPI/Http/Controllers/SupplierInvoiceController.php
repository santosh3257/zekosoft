<?php

namespace Modules\RestAPI\Http\Controllers;

use Froiden\RestAPI\ApiResponse;
use Modules\RestAPI\Entities\Invoice;
use App\Models\InvoiceItems;
use App\Models\VatPercentage;
use Modules\RestAPI\Entities\SettingInvoice;
use App\Models\HouseService;
use App\Models\Tax;
use App\Models\TaxAccountNumber;
use App\Models\VatTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RestAPI\Entities\User;
use Modules\RestAPI\Entities\Product;

class SupplierInvoiceController extends ApiBaseController {

    public function getMySupplierInvoice(){

        $user = auth()->user()->user;
        $clients = $this->getuserIdMeAsClient($user->user_auth_id, $user->company_id);
        $query = DB::table('invoices')->join('users', 'users.id', '=', 'invoices.client_id')->Leftjoin('currencies', 'currencies.id', '=', 'invoices.currency_id')->whereIn('invoices.client_id',$clients);
        $invoices = $query->select('invoices.id', 'invoices.client_id', 'users.name', 'invoices.invoice_number', 'invoices.issue_date', 'invoices.due_date', 'invoices.total','invoices.company_id', 'invoices.status', 'invoices.is_archived', 'currencies.currency_symbol')->orderBy('invoices.id', 'desc')->paginate(10);
        $data = array(
            "status" => true,
            'invoices' => $invoices

        );
        return Response()->json($data, $this->successStatus);
    }

    public function editMySupplierInvoice($supplierInvoiceId){
        $invoice =  DB::table('invoices')->where('id', $supplierInvoiceId)->first();
        // $invoice = Invoice::with('houseService', 'items.product', 'items.houseWork', 'items.TaxInfo', 'items.accountCode', '')->where('id', $supplierInvoiceId)->first();
        if($invoice){
            $invoice->currency = DB::table('currencies')->where('id', $invoice->currency_id)->first();
            $items = DB::table('invoice_items')->where('invoice_id', $invoice->id)->get();
            $invoice->item  = $items;
            if($items){
                foreach($items as $key=>$item){
                    if($item->product_id){
                        $invoice->item[$key]->product = DB::table('products')->where('id', $item->product_id)->first();
                    }
                    if ($item->house_work_id) {
                        $invoice->item[$key]->houseWork = DB::table('house_works')->where('id', $item->house_work_id)->first();
                    }

                    if ($item->tax_id) {
                        $invoice->item[$key]->TaxInfo = DB::table('vat_percentage')->where('id', $item->tax_id)->first();
                    }

                    if ($item->account_code_id) {
                        $invoice->item[$key]->account_code = DB::table('tax_account_numbers')->where('id', $item->account_code_id)->first();
                    }

                }
            }
            $invoice->houseService = HouseService::where('id',$invoice->house_service_id)->first();
        }


            // Active Articles
            $products = DB::table('products')->select('id', 'name', 'price', 'taxes', 'account_code', 'in_stock')->where('company_id', $invoice->company_id)->get();
            if ($products) {
                foreach ($products as $key => $product) {
                    $products[$key]->text = $product->name;
                }
            }

            // House Services
            $houseServices = HouseService::with('works')->get();
            if ($houseServices) {
                foreach ($houseServices as $key => $service) {
                    $houseServices[$key]->text = $service->service_name;
                    if (!empty($service->works)) {
                        foreach ($service->works as $idx => $work) {
                            $houseServices[$key]->works[$idx]->text = $work->work_name;
                        }
                    }
                }
            }
            $invoiceSetting = SettingInvoice::where('invoice_id', $supplierInvoiceId)->first();
            // Vat Types
            $vatTypes = VatTypes::where('status', '')->get();
            if ($vatTypes) {
                foreach ($vatTypes as $key => $type) {
                    $vatTypes[$key]->text = $type->vat_type;
                }
            }
            // Taxes
            $taxes = VatPercentage::get();
            // Account Codes
            $accountCodes = TaxAccountNumber::where('status', 'active')->orderBy('account_number', 'asc')->get();

            $data = array(
                "status" => true,
                'invoice' => $invoice,
                'products' => $products,
                'taxes' => $taxes,
                'accountCodes' => $accountCodes,
                'houseServices' => $houseServices,
                'vatTypes' => $vatTypes,
                'invoiceSetting' => $invoiceSetting,

            );
            return Response()->json($data, $this->successStatus);
        
    }

    public function getuserIdMeAsClient($authId, $myCompanyId = null)
    {
        $asClients = DB::table('users')
            ->select('id')
            ->where('company_id', '<>', $myCompanyId)
            ->where('user_auth_id', $authId)
            ->get();
        $clientIdArray = [];
        if($asClients){
            foreach($asClients as $client){
                array_push($clientIdArray, $client->id);
            }
        }
        return $clientIdArray;
    }

}
