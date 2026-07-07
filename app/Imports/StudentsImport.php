<?php

namespace App\Imports;

use App\Models\AcademicYear;
use App\Models\ImportLog;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;

class StudentsImport implements OnEachRow, ShouldQueue, WithChunkReading, WithEvents, WithHeadingRow
{
    public function __construct(private readonly int $importLogId)
    {
    }

    public function onRow(Row $row): void
    {
        $data = $row->toArray();
        $rowNumber = $row->getIndex() + 1;

        $validator = Validator::make($data, [
            'student_code' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'digits:13'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'integer', 'min:2500', 'max:2700'],
            'program' => ['nullable', 'string', 'max:255'],
            'degree_level' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'in:studying,graduated,dropped_out'],
        ]);

        if ($validator->fails()) {
            $this->recordFailure($rowNumber, $validator->errors()->all());

            return;
        }

        $code = trim((string) $data['student_code']);
        $nationalId = isset($data['national_id']) && $data['national_id'] !== '' ? trim((string) $data['national_id']) : null;

        if (Student::where('student_code', $code)->exists()) {
            $this->recordFailure($rowNumber, ["รหัสนักศึกษา {$code} ซ้ำกับข้อมูลที่มีอยู่แล้ว"]);

            return;
        }

        if ($nationalId && Student::where('national_id', $nationalId)->exists()) {
            $this->recordFailure($rowNumber, ["เลขบัตรประชาชน {$nationalId} ซ้ำกับข้อมูลที่มีอยู่แล้ว"]);

            return;
        }

        $academicYear = AcademicYear::firstOrCreate(
            ['year' => (int) $data['academic_year']],
            ['is_active' => false]
        );

        try {
            Student::create([
                'academic_year_id' => $academicYear->id,
                'student_code' => $code,
                'national_id' => $nationalId,
                'prefix' => $data['prefix'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'program' => $data['program'] ?? null,
                'degree_level' => $data['degree_level'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => $data['status'] ?? 'studying',
            ]);
        } catch (QueryException $e) {
            // Guards against a race condition between the exists() checks above
            // and the insert, if two workers process near-duplicate rows at once.
            $this->recordFailure($rowNumber, ['ข้อมูลซ้ำกับแถวอื่นที่กำลังนำเข้าพร้อมกัน']);

            return;
        }

        ImportLog::whereKey($this->importLogId)->increment('imported_rows');
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

    private function recordFailure(int $rowNumber, array $messages): void
    {
        $importLog = ImportLog::find($this->importLogId);

        if (! $importLog) {
            return;
        }

        $errors = $importLog->errors ?? [];
        $errors[] = ['row' => $rowNumber, 'messages' => $messages];

        $importLog->update(['errors' => $errors]);
        $importLog->increment('failed_rows');
    }
}
