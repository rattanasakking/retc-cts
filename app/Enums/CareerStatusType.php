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
            self::Employed => '#00e5ff',
            self::Unemployed => '#ff453a',
            self::FurtherStudy => '#32d74b',
            self::MilitaryService => '#ffd60a',
            self::Entrepreneur => '#bf5af2',
            self::Other => '#98989d',
        };
    }
}
