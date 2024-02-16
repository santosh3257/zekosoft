<?php

namespace App\Exports;

use Modules\RestAPI\Entities\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportVendors implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = auth()->user()->user;
        return Vendor::select('name', 'ssn', 'vat_number','email','bankgiro','bank_fee','billing_address','country','state','city','postal_code')->where('added_by',$user->id)->get();
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function headings(): array
    {
        return [
        "Name", 
        "Ssn", 
        "Vat Number", 
        "Email", 
        "Bankgiro", 
        "Bank Fee",
        "Billing Address", 
        "Country", 
        "State",
        "City", 
        "Postal Code"
    ];
    }

    /**
    * @var $clients
    */
    public function map($vendor): array
    {
        return [
            $vendor->name,
            $vendor->ssn,
            $vendor->vat_number,
            $vendor->email,
            $vendor->bankgiro,
            $vendor->bank_fee,
            $vendor->billing_address,
            $vendor->country,
            $vendor->state,
            $vendor->city,
            $vendor->postal_code,
        ];
    }
}
