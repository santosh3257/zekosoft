<?php

namespace Modules\RestAPI\Entities;
use Froiden\RestAPI\ApiModel;

class EstimateEmail extends ApiModel
{
    // region Properties

    protected $table = 'estimate_emails';

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
