<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportArticles implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Product::all();
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function headings(): array
    {
        return ["Name", "Type", "Purchase Amount", "In Stock", "Rate", "Note"];
    }

    /**
    * @var $clients
    */
    public function map($article): array
    {
        return [
            $article->name,
            $article->type,
            $article->purchase_amount,
            $article->in_stock,
            $article->price,
            $article->note,
        ];
    }
}
