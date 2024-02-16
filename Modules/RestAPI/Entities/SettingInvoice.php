<?php

namespace Modules\RestAPI\Entities;
use Froiden\RestAPI\ApiModel;

class SettingInvoice extends ApiModel
{
    // region Properties

    protected $table = 'setting_invoice';

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
