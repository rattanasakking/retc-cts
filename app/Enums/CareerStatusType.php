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
            self::Employed => '#2563a8',
            self::Unemployed => '#b5484a',
            self::FurtherStudy => '#4fb3a0',
            self::MilitaryService => '#a67c1f',
            self::Entrepreneur => '#7c6fd6',
            self::Other => '#8b98a5',
        };
    }
}
