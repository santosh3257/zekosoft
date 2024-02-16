<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HouseWork;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HouseService extends Model
{
    use HasFactory;

    public function works():HasMany
    {
        return $this->hasMany(HouseWork::class, 'service_id');
    }
}
