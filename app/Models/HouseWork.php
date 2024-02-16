<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\HouseService;

class HouseWork extends BaseModel
{
    use SoftDeletes;

    public function houseService(): BelongsTo
    {
        return $this->belongsTo(HouseService::class, 'service_id');

    }
}
