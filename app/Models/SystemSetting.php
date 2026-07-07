<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'system_name',
        'college_name',
        'logo_path',
    ];

    /**
     * This is a single-row table — always fetch (or lazily create) row #1
     * instead of querying by arbitrary criteria.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['system_name' => 'RETC-CTS']);
    }
}
