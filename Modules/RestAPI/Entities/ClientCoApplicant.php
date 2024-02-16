<?php

namespace Modules\RestAPI\Entities;

use Modules\RestAPI\Entities\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\ClientCoApplicantObserver;
use Froiden\RestAPI\ApiModel;

class ClientCoApplicant extends ApiModel
{
    // region Properties

    protected $table = 'client_co_applicants';

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
