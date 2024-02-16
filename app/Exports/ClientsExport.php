<?php

namespace App\Exports;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClientsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::allMyClients();
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function headings(): array
    {
        return ["Name*", "Email*","Mobile","Social Security Number*","Account Type*","Company Name","Address*","City*","State*","Postal Code*","Company Phone","Company Website","Gst No"];
    }

    /**
    * @var $clients
    */
    public function map($user): array
    {
        
        return [
            $user->name,
            $user->email,
            $user->mobile,
            $user->social_security,
            $user->account_type,
            $user->company_name,
            $user->address,
            $user->city,
            $user->state,
            $user->postal_code,
            $user->website,
            $user->office_phone,
            $user->gst_number,
            
            
        ];
    } 

}
