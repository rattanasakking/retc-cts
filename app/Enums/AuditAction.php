<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case ImportCsv = 'import_csv';
    case ExportExcel = 'export_excel';
    case ExportPdf = 'export_pdf';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
    case ForceDelete = 'force_delete';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'เข้าสู่ระบบ',
            self::Logout => 'ออกจากระบบ',
            self::ImportCsv => 'นำเข้าข้อมูล CSV',
            self::ExportExcel => 'ส่งออก Excel',
            self::ExportPdf => 'ส่งออก PDF',
            self::Create => 'สร้างข้อมูล',
            self::Update => 'แก้ไขข้อมูล',
            self::Delete => 'ลบข้อมูล',
            self::Restore => 'กู้คืนข้อมูล',
            self::ForceDelete => 'ลบข้อมูลถาวร',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Login, self::Create, self::Restore => 'badge-success',
            self::Logout => 'badge-ghost',
            self::ImportCsv, self::ExportExcel, self::ExportPdf => 'badge-info',
            self::Update => 'badge-warning',
            self::Delete, self::ForceDelete => 'badge-error',
        };
    }
}
