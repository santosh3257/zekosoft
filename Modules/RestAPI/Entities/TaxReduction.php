<?php

namespace Modules\RestAPI\Entities;

use Modules\RestAPI\Entities\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\TaxReductionObserver;
use Froiden\RestAPI\ApiModel;

class TaxReduction extends ApiModel
{
    // region Properties

    protected $table = 'house_work_tax_reduction';

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
