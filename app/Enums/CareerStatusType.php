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
            self::Employed => '#2563eb',
            self::Unemployed => '#e11d48',
            self::FurtherStudy => '#0d9488',
            self::MilitaryService => '#d97706',
            self::Entrepreneur => '#7c3aed',
            self::Other => '#64748b',
        };
    }
}
