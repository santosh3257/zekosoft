<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;
use Modules\RestAPI\Entities\Product;
use Modules\RestAPI\Entities\Tax;
use Illuminate\Database\Eloquent\Relations\belongsTo;

class BillItems extends ApiModel
{
    // region Properties

    protected $table = 'bill_items';

    protected $default = [
        'id',
    ];

    protected $hidden = [

    ];

    protected $guarded = [
        'id',
    ];

    protected $filterable = [
        'id',
    ];
    public function articalInfo()
    {
        return $this->belongsTo(Product::class, 'article_id', 'id')->select(['id','name']);
    }
    public function taxInfo()
    {
        return $this->belongsTo(Tax::class, 'tax_id', 'id')->select(['id','rate_percent']);
    }

}
