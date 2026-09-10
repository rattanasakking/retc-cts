<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * A juristic person the workplace field can autocomplete to. Rows come from
 * the DBD open dataset ("dbd"), from OpenStreetMap ("osm"), or from a name
 * somebody typed that was not in the list yet ("entered").
 */
class Company extends Model
{
    use HasFactory;

    /**
     * Wrappers that appear on nearly every registered name and are never what
     * someone types when searching for the place they work.
     */
    private const NOISE = [
        'บริษัท', 'บมจ.', 'บมจ', 'บจก.', 'บจก', 'บจ.',
        'ห้างหุ้นส่วนจำกัด', 'ห้างหุ้นส่วนสามัญ', 'หจก.', 'หจก', 'หสน.', 'หส.',
        'จำกัด', '(มหาชน)', 'มหาชน', 'สาขา',
        'company limited', 'co., ltd.', 'co.,ltd.', 'co ltd', 'ltd.', 'ltd', 'plc.', 'plc',
        'limited partnership', 'partnership',
    ];

    public const COMPANY = 'company';

    public const INSTITUTION = 'institution';

    protected $fillable = [
        'name',
        'kind',
        'search_name',
        'juristic_id',
        'type',
        'status',
        'province',
        'district',
        'source',
    ];

    protected static function booted(): void
    {
        // Kept in sync here rather than at each call site — every path that
        // writes a company (import, OSM pick, a student typing one in) needs it.
        static::saving(function (self $company) {
            $company->search_name = static::normalise($company->name);
        });
    }

    /**
     * Strips the juristic wrapper and punctuation, leaving the part of the name
     * a person would actually say out loud.
     */
    public static function normalise(?string $name): string
    {
        $name = mb_strtolower(trim((string) $name));

        foreach (self::NOISE as $noise) {
            $name = str_replace(mb_strtolower($noise), ' ', $name);
        }

        // Punctuation carries no meaning here and differs between sources.
        $name = preg_replace('/[()\[\]{}.,\-–—_"\'\/\\\\]+/u', ' ', $name);

        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    /**
     * Every word the person typed has to appear somewhere in the name, in any
     * order — "ออลล์ ซีพี" finds "บริษัท ซีพี ออลล์ จำกัด (มหาชน)" just as
     * "ซีพี ออลล์" does. Names that start with what was typed come first.
     */
    public function scopeMatching(Builder $query, string $term): Builder
    {
        $normalised = static::normalise($term);
        $words = array_filter(explode(' ', $normalised));

        if ($words === []) {
            // Nothing left after stripping — fall back to the raw text so a
            // search for literally "บริษัท" still returns something.
            $words = [trim($term)];
        }

        foreach ($words as $word) {
            $query->where(function ($q) use ($word) {
                $q->where('search_name', 'like', '%'.$word.'%')
                    ->orWhere('name', 'like', '%'.$word.'%');
            });
        }

        return $query
            ->orderByRaw('case when search_name like ? then 0 else 1 end', [$normalised.'%'])
            ->orderBy('name');
    }

    /** สถานประกอบการ หรือ สถานศึกษา — คนละรายการกันเวลาเสนอให้เลือก */
    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }

    /**
     * Remembers a workplace somebody typed in, so the next person filling the
     * form gets it as a suggestion. Never overwrites a row imported from DBD.
     */
    public static function remember(?string $name, array $attributes = []): void
    {
        $name = trim((string) $name);

        if ($name === '' || mb_strlen($name) > 255) {
            return;
        }

        // Remembering is a courtesy to the next person filling the form; it
        // must never cost this one their submission.
        try {
            static::firstOrCreate(['name' => $name], array_merge(['source' => 'entered'], $attributes));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
