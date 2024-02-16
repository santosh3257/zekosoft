<?php

namespace App\Exports;

use Modules\RestAPI\Entities\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportExpenses implements FromCollection, WithHeadings, WithMapping
{
     /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = auth()->user()->user;
        return Expense::select('item_name', 'purchase_date', 'price','currency_id','status','total_amount','sub_total','tax_amount','tax_id','account_code_id','language_id','assign_to','assign_to_id')->where('added_by',$user->id)->get();
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function headings(): array
    {
        return [
        "Item Name", 
        "Date", 
        "Price", 
        "Currency Id", 
        "Status", 
        "Total Amount",
        "Sub Amount", 
        "Tax Amount", 
        "Tax Id",
        "Account Code Id", 
        "Language Id",
        "Assign To",
        "Assign To Id"
    ];
    }

    /**
    * @var $clients
    */
    public function map($expense): array
    {
        return [
            $expense->item_name,
            $expense->purchase_date,
            $expense->price,
            $expense->currency_id,
            $expense->status,
            $expense->total_amount,
            $expense->sub_total,
            $expense->tax_amount,
            $expense->tax_id,
            $expense->account_code_id,
            $expense->language_id,
            $expense->assign_to,
            $expense->assign_to_id,
        ];
    }
}
