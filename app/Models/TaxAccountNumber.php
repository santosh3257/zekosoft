<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\belongsTo;
use App\Models\VatPercentage;
use App\Models\VatTypes;
class TaxAccountNumber extends BaseModel
{
    use SoftDeletes;


    public function vatPercentage(): belongsTo
    {
        return $this->belongsTo(VatPercentage::class, 'vat_percentage_id','id');
    }

    public function vatType(): belongsTo
    {
        return $this->belongsTo(VatTypes::class, 'vat_type_id','id');
    }
}
