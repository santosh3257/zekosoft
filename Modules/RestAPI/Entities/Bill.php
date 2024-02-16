<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends ApiModel
{
    // region Properties

    protected $table = 'bills';

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
    public function billItems(): HasMany
    {
        return $this->hasMany(BillItems::class, 'bill_id', 'id');
    }
    public function billPayments(): HasMany
    {
        return $this->hasMany(AddBillPayment::class, 'bill_id', 'id');
    }
    public function vendorInfo()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id')->select(['id','name']);
    }

}
