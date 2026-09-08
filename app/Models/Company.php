<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A juristic person the workplace field can autocomplete to. Rows come either
 * from the DBD open dataset (source "dbd") or from a name somebody typed that
 * was not in the list yet (source "entered").
 */
class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'juristic_id',
        'type',
        'status',
        'province',
        'district',
        'source',
    ];

    /**
     * Names matching what has been typed so far.
     *
     * Ordered so exact prefix matches come first — someone typing "ซีพี"
     * wants the companies starting with it, not the ones that merely contain
     * it somewhere in a long name.
     */
    public function scopeMatching(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query
            ->where('name', 'like', '%'.$term.'%')
            ->orderByRaw('case when name like ? then 0 else 1 end', [$term.'%'])
            ->orderBy('name');
    }

    /**
     * Remembers a workplace somebody typed in, so the next person filling the
     * form gets it as a suggestion. Never overwrites a row imported from DBD.
     */
    public static function remember(?string $name): void
    {
        $name = trim((string) $name);

        if ($name === '' || mb_strlen($name) > 255) {
            return;
        }

        static::firstOrCreate(['name' => $name], ['source' => 'entered']);
    }
}
