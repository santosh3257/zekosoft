<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class ImportArticle implements ToArray
{
    public static function fields(): array
    {
        return array(
            array('id' => 'name', 'name' => __('app.name'), 'required' => 'Yes'),
            array('id' => 'type', 'name' => __('app.type'), 'required' => 'Yes'),
            array('id' => 'purchase_amount', 'name' => __('app.purchase_amount'), 'required' => 'No'),
            array('id' => 'in_stock', 'name' => __('app.in_stock'), 'required' => 'No'),
            array('id' => 'rate', 'name' => __('app.rate'), 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        return $array;
    }
}
