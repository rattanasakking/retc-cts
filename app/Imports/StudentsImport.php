<?php

namespace App\Imports;

use App\Imports\Concerns\ImportsStudentRow;
use App\Models\ImportLog;
use App\Support\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, ShouldQueue, WithChunkReading, WithEvents, WithHeadingRow
{
    use ImportsStudentRow;

    public function __construct(private readonly int $importLogId, private readonly bool $updateExisting = false)
    {
    }

    public function onRow(Row $row): void
    {
        $this->validateAndCreateStudent($row->getIndex() + 1, $row->toArray());
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                ImportLog::whereKey($this->importLogId)->update([
                    'status' => 'processing',
                    'started_at' => now(),
                ]);
            },
            AfterImport::class => function (AfterImport $event) {
                $importLog = ImportLog::find($this->importLogId);

                if (! $importLog) {
                    return;
                }

                $importLog->update([
                    'status' => $importLog->imported_rows === 0 && $importLog->failed_rows > 0 ? 'failed' : 'completed',
                    'finished_at' => now(),
                ]);

                // Uses the uploader's user_id captured on the ImportLog row,
                // not auth()->id() — this callback runs inside a queued job
                // with no authenticated session of its own.
                AuditLogger::log(
                    action: 'import_csv',
                    module: 'นำเข้าข้อมูล',
                    description: "นำเข้าข้อมูลนักศึกษาจากไฟล์ {$importLog->file_name} (สำเร็จ {$importLog->imported_rows} แถว, ล้มเหลว {$importLog->failed_rows} แถว)",
                    userId: $importLog->user_id,
                );
            },
        ];
    }
}
