<?php

namespace App\Imports\Concerns;

use App\Models\AcademicYear;
use App\Models\ImportLog;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

/**
 * Shared by every StudentsImport-style class (each importLogId-keyed and
 * ShouldQueue): validates a normalized row, enforces student_code/national_id
 * uniqueness, creates the Student, and records failures against the
 * ImportLog — regardless of which source format the row came from.
 */
trait ImportsStudentRow
{
    /**
     * @param  array<string, mixed>  $data  student_code, national_id, prefix,
     *      first_name, last_name, academic_year, program, degree_level,
     *      phone, email, status — same shape StudentsImport's CSV template
     *      uses; callers translate their own source format into this shape.
     */
    private function validateAndCreateStudent(int $rowNumber, array $data): ?Student
    {
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

            return null;
        }

        $code = trim((string) $data['student_code']);
        $nationalId = isset($data['national_id']) && $data['national_id'] !== '' ? trim((string) $data['national_id']) : null;

        if (Student::where('student_code', $code)->exists()) {
            $this->recordFailure($rowNumber, ["รหัสนักศึกษา {$code} ซ้ำกับข้อมูลที่มีอยู่แล้ว"]);

            return null;
        }

        if ($nationalId && Student::where('national_id', $nationalId)->exists()) {
            $this->recordFailure($rowNumber, ["เลขบัตรประชาชน {$nationalId} ซ้ำกับข้อมูลที่มีอยู่แล้ว"]);

            return null;
        }

        $academicYear = AcademicYear::firstOrCreate(
            ['year' => (int) $data['academic_year']],
            ['is_active' => false]
        );

        try {
            $student = Student::create([
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
            // Guards against a race condition between the exists() checks
            // above and the insert, if two workers process near-duplicate
            // rows at once.
            $this->recordFailure($rowNumber, ['ข้อมูลซ้ำกับแถวอื่นที่กำลังนำเข้าพร้อมกัน']);

            return null;
        }

        ImportLog::whereKey($this->importLogId)->increment('imported_rows');

        return $student;
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
