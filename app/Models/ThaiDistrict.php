<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThaiDistrict extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'name_th', 'province_id'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(ThaiProvince::class, 'province_id');
    }

    public function subdistricts(): HasMany
    {
        return $this->hasMany(ThaiSubdistrict::class, 'district_id');
    }
}
