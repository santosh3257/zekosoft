<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TaxAccountNumber;

class VatPercentage extends Model
{
    protected $table = 'vat_percentage';

    protected $fillable = [
        'id',
        'percentage'
    ];

    protected $hidden = [

    ];

    protected $guarded = [
        'id',
    ];

    protected $filterable = [
        'id',
    ];

    public function accountCodes(): HasMany
    {
        return $this->HasMany(TaxAccountNumber::class, 'vat_percentage_id','id');
    }
}
