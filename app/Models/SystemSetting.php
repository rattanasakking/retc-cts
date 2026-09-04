<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemSetting extends Model
{
    protected $fillable = [
        'system_name',
        'short_name',
        'college_name',
        'logo_path',
        'primary_color',
        'contact_email',
        'contact_phone',
    ];

    /** Resolved once per request — every layout asks for it. */
    private static ?self $cached = null;

    /**
     * This is a single-row table — always fetch (or lazily create) row #1
     * instead of querying by arbitrary criteria.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['system_name' => 'RETC-CTS']);
    }

    /**
     * Same row, but safe to call from anywhere that renders a page: it never
     * writes, never throws, and hits the database at most once per request.
     * Falls back to an unsaved default so the site still renders before the
     * table exists (a fresh clone that has not been migrated yet).
     */
    public static function cached(): self
    {
        if (static::$cached instanceof self) {
            return static::$cached;
        }

        try {
            return static::$cached = static::query()->find(1) ?? new static(['system_name' => 'RETC-CTS']);
        } catch (Throwable) {
            return static::$cached = new static(['system_name' => 'RETC-CTS']);
        }
    }

    /** Drops the per-request cache after the settings screen saves. */
    public static function forgetCached(): void
    {
        static::$cached = null;
    }

    public function displayName(): string
    {
        return $this->system_name ?: 'RETC-CTS';
    }

    /** For the sidebar, the mobile header and the PWA — falls back to the full name. */
    public function displayShortName(): string
    {
        return $this->short_name ?: $this->displayName();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function brandColor(): string
    {
        return $this->primary_color ?: '#00e5ff';
    }

    /**
     * Text colour to place on top of brandColor(). Picked by luminance so a
     * college choosing a pale brand colour doesn't end up with white text on
     * a near-white button.
     */
    public function brandContentColor(): string
    {
        [$r, $g, $b] = sscanf($this->brandColor(), '#%02x%02x%02x') ?: [0, 229, 255];

        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        return $luminance > 0.6 ? '#11181f' : '#ffffff';
    }
}
