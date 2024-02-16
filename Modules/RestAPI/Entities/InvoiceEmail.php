<?php

namespace Modules\RestAPI\Entities;

use Modules\RestAPI\Entities\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\InvoiceEmailObserver;
use Froiden\RestAPI\ApiModel;

class InvoiceEmail extends ApiModel
{
    // region Properties

    protected $table = 'invoice_emails';

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
