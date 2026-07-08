<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThaiSubdistrict extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'name_th', 'district_id'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(ThaiDistrict::class, 'district_id');
    }
}
