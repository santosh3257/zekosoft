<?php

namespace Modules\OpenPayment\Entities;

use Froiden\RestAPI\ApiModel;
class OpenpaymentUserAccount extends ApiModel
{
    // region Properties

    protected $table = 'openpayment_user_accounts';

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
