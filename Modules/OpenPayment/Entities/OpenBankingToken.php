<?php

namespace Modules\OpenPayment\Entities;

use Illuminate\Database\Eloquent\Builder;
use Modules\RestAPI\Observers\OpenBankingTokenObserver;
use Froiden\RestAPI\ApiModel;
class OpenBankingToken extends ApiModel
{
    // region Properties

    protected $table = 'open_banking_tokens';

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
        'token',
        'created_at',
        'updated_at'
    ];

}
