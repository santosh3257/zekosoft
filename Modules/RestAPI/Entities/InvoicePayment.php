<?php

namespace Modules\RestAPI\Entities;

use Modules\RestAPI\Entities\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\InvoicePaymentObserver;
use Froiden\RestAPI\ApiModel;

class InvoicePayment extends ApiModel
{
    // region Properties

    protected $table = 'invoice_payments';

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
