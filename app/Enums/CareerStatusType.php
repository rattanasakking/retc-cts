<?php

namespace App\Enums;

enum CareerStatusType: string
{
    case Employed = 'employed';
    case Unemployed = 'unemployed';
    case FurtherStudy = 'further_study';
    case MilitaryService = 'military_service';
    case Entrepreneur = 'entrepreneur';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Employed => 'ทำงานแล้ว',
            self::Unemployed => 'ว่างงาน',
            self::FurtherStudy => 'ศึกษาต่อ',
            self::MilitaryService => 'เกณฑ์ทหาร',
            self::Entrepreneur => 'ประกอบธุรกิจส่วนตัว',
            self::Other => 'อื่นๆ',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Employed => '#3b82f6',
            self::Unemployed => '#f87171',
            self::FurtherStudy => '#22c55e',
            self::MilitaryService => '#fbbf24',
            self::Entrepreneur => '#a855f7',
            self::Other => '#94a3b8',
        };
    }
}
