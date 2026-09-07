<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Thai date formatting shared by every screen that shows one.
 *
 * The app runs on the "en" locale, so Carbon's own diffForHumans() and month
 * names come back in English — and Thai readers expect พ.ศ. years, not ค.ศ.
 */
class ThaiDate
{
    /** 04/09/2569 */
    public static function short(DateTimeInterface $moment): string
    {
        $moment = Carbon::instance($moment);

        return $moment->format('d/m/').($moment->format('Y') + 543);
    }

    /** 04/09/2569 14:29 */
    public static function shortWithTime(DateTimeInterface $moment): string
    {
        return static::short($moment).' '.Carbon::instance($moment)->format('H:i');
    }

    /** "3 ชั่วโมงที่แล้ว" */
    public static function relative(DateTimeInterface $moment): string
    {
        $minutes = Carbon::instance($moment)->diffInMinutes(now());

        return match (true) {
            $minutes < 1 => 'เมื่อสักครู่',
            $minutes < 60 => floor($minutes).' นาทีที่แล้ว',
            $minutes < 1440 => floor($minutes / 60).' ชั่วโมงที่แล้ว',
            $minutes < 43200 => floor($minutes / 1440).' วันที่แล้ว',
            default => floor($minutes / 43200).' เดือนที่แล้ว',
        };
    }
}
