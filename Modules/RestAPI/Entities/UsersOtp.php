<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class UsersOtp extends ApiModel
{
    // region Properties

    protected $table = 'users_otp';

    protected $fillable = [
        'id',
        'user_id',
        'otp',
        'expireDate',
        'verified'
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
