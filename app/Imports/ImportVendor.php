<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class ImportVendor implements ToArray
{
    public static function fields(): array
    {
        return array(
            array('id' => 'name', 'name' => __('app.name'), 'required' => 'Yes'),
            array('id' => 'ssn', 'name' => __('app.ssn'), 'required' => 'Yes'),
            array('id' => 'vat_number', 'name' => __('app.vat_number'), 'required' => 'yes'),
            array('id' => 'email', 'name' => __('app.email'), 'required' => 'Yes'),
            array('id' => 'bankgiro', 'name' => __('app.bankgiro'), 'required' => 'Yes'),
            array('id' => 'bank_fee', 'name' => __('app.bank_fee'), 'required' => 'Yes'),
            array('id' => 'billing_address', 'name' => __('app.billing_address'), 'required' => 'Yes'),
            array('id' => 'country', 'name' => __('app.country'), 'required' => 'Yes'),
            array('id' => 'state', 'name' => __('app.state'), 'required' => 'Yes'),
            array('id' => 'city', 'name' => __('app.city'), 'required' => 'Yes'),
            array('id' => 'postal_code', 'name' => __('app.postal_code'), 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }
}
