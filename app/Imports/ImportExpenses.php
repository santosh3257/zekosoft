<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class ImportExpenses implements ToArray
{
    public static function fields(): array
    {
        return array(
            array('id' => 'item_name', 'name' => __('app.item_name'), 'required' => 'Yes'),
            array('id' => 'purchase_date', 'name' => __('app.purchase_date'), 'required' => 'Yes'),
            array('id' => 'price', 'name' => __('app.price'), 'required' => 'yes'),
            array('id' => 'currency_id', 'name' => __('app.currency_id'), 'required' => 'Yes'),
            array('id' => 'status', 'name' => __('app.status'), 'required' => 'Yes'),
            array('id' => 'total_amount', 'name' => __('app.total_amount'), 'required' => 'Yes'),
            array('id' => 'sub_total', 'name' => __('app.sub_total'), 'required' => 'Yes'),
            array('id' => 'tax_amount', 'name' => __('app.tax_amount'), 'required' => 'Yes'),
            array('id' => 'tax_id', 'name' => __('app.tax_id'), 'required' => 'Yes'),
            array('id' => 'account_code_id', 'name' => __('app.account_code_id'), 'required' => 'Yes'),
            array('id' => 'language_id', 'name' => __('app.language_id'), 'required' => 'Yes'),
            array('id' => 'assign_to', 'name' => __('app.assign_to'), 'required' => 'Yes'),
            array('id' => 'assign_to_id', 'name' => __('app.assign_to_id'), 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }
}
