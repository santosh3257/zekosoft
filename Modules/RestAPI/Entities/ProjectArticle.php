<?php

namespace Modules\RestAPI\Entities;

use Froiden\RestAPI\ApiModel;

class ProjectArticle extends ApiModel
{
    // region Properties

    protected $table = 'project_articles';

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
