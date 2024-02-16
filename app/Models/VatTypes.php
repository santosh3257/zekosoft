<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\VatPercentage;

class VatTypes extends Model
{
    use HasFactory;

    public function vatPercentage(): belongsTo
    {
        return $this->belongsTo(VatPercentage::class, 'vat_percentage_id','id');
    }
}
