<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Executive = 'executive';
    case DepartmentHead = 'department_head';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'ผู้ดูแลระบบ',
            self::Teacher => 'อาจารย์',
            self::Executive => 'ผู้บริหาร',
            self::DepartmentHead => 'หัวหน้าสาขา/แผนก',
        };
    }
}
