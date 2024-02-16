<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class Vendor extends ApiModel
{
    // region Properties

    protected $table = 'vendors';

    protected $default = [
        'id',
    ];

    protected $hidden = ['setting_currency', 'setting_language','tax_id'

    ];

    protected $guarded = [
        'id',
    ];

    protected $filterable = [
        'id',
    ];

}
