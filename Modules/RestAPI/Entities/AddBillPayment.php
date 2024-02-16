<?php

namespace Modules\RestAPI\Entities;

use Modules\RestAPI\Entities\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\AddBillPaymentObserver;
use Froiden\RestAPI\ApiModel;

class AddBillPayment extends ApiModel
{
    // region Properties

    protected $table = 'add_bill_payments';

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

}
