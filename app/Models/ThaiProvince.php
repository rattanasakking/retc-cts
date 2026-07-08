<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThaiProvince extends Model
{
    public $timestamps = false;

    protected $fillable = ['id', 'name_th', 'lat', 'lng'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(ThaiDistrict::class, 'province_id');
    }
}
