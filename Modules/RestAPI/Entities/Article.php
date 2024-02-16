<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class Article extends ApiModel
{
    // region Properties

    protected $table = 'articles';

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
