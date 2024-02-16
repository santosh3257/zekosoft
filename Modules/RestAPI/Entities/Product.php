<?php

namespace Modules\RestAPI\Entities;

use App\Observers\ProductObserver;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends \App\Models\Product
{
    // region Properties
    use SoftDeletes;
    protected $table = 'products';

    protected $default = [
        'id',
        'name',
        'description',
        'price',
        'taxes',
    ];

    protected $filterable = [
        'id',
        'name',
        'description',
        'price',
        'taxes',
    ];

    protected $hidden = ['tax','total_amount', 'image_url', 'download_file_url'];

    public static function boot()
    {
        parent::boot();
        static::observe(ProductObserver::class);
    }
}
