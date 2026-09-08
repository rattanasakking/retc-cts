<?php

namespace App\Console\Commands;

use App\Support\CompanyImporter;
use Illuminate\Console\Command;
use Throwable;

/**
 * CLI equivalent of ตั้งค่าระบบ → ฐานข้อมูลบริษัท, for a file too large to
 * push through a browser upload or for a scheduled refresh.
 */
class ImportCompanies extends Command
{
    protected $signature = 'companies:import
                            {file : path to a DBD open-data juristic person CSV}
                            {--province= : keep only rows in this province}';

    protected $description = 'นำเข้ารายชื่อนิติบุคคลจากไฟล์ CSV ของ DBD Open Data';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("ไม่พบไฟล์: {$file}");

            return self::FAILURE;
        }

        $this->info('กำลังนำเข้า...');

        try {
            $result = (new CompanyImporter)->import($file, $this->option('province'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['เพิ่มใหม่', 'อัปเดต', 'ข้าม'],
            [[$result['imported'], $result['updated'], $result['skipped']]]
        );

        return self::SUCCESS;
    }
}
