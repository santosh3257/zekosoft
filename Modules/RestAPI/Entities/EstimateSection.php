<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class EstimateSection extends ApiModel
{
    // region Properties

    protected $table = 'estimate_sections';

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
