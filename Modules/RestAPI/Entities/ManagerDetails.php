<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class ManagerDetails extends ApiModel
{
    protected $table = 'manager_details';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'social_security_number',
        'address',
        'state',
        'city',
        'postal_code',
        'website',
        'note'
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
