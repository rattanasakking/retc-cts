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
 *
 * Using classes are expected to expose a `bool $updateExisting` property
 * (constructor-set, defaults to false in practice) — when true, a row whose
 * student_code already matches an active student fills in that student's
 * currently-empty fields instead of being rejected as a duplicate. Existing
 * non-empty values are never overwritten, and no CareerStatus is created for
 * an update (only for a genuinely new student) — callers should treat a null
 * return as "nothing more to do for this row" either way.
 */
trait ImportsStudentRow
{
    /**
     * @param  array<string, mixed>  $data  student_code, national_id, prefix,
     *      first_name, last_name, birth_date, academic_year, program,
     *      degree_level, phone, email, status — same shape StudentsImport's
     *      CSV template uses; callers translate their own source format
     *      into this shape. birth_date must already be a Y-m-d string.
     */
    private function validateAndCreateStudent(int $rowNumber, array $data): ?Student
    {
        $validator = Validator::make($data, [
            'student_code' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'digits:13'],
            'prefix' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
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

        $existing = Student::where('student_code', $code)->first();

        if ($existing) {
            if (! $this->updateExisting) {
                $this->recordFailure($rowNumber, ["รหัสนักศึกษา {$code} ซ้ำกับข้อมูลที่มีอยู่แล้ว"]);

                return null;
            }

            $this->fillMissingFields($existing, $data, $rowNumber);

            return null;
        }

        // withTrashed(): student_code/national_id are unique at the DB
        // level regardless of soft-delete state, so a code freed up by
        // deleting a student is NOT actually reusable — checking only
        // active rows here would pass, then fail at the INSERT with a
        // confusing generic "concurrent duplicate" message instead of
        // naming the real conflict. A trashed record can't be "updated"
        // via import either — it must be restored first.
        if (Student::withTrashed()->where('student_code', $code)->exists()) {
            $this->recordFailure($rowNumber, ["รหัสนักศึกษา {$code} ซ้ำกับข้อมูลที่มีอยู่แล้ว (หรือเคยถูกลบไปก่อนหน้านี้)"]);

            return null;
        }

        if ($nationalId && Student::withTrashed()->where('national_id', $nationalId)->exists()) {
            $this->recordFailure($rowNumber, ["เลขบัตรประชาชน {$nationalId} ซ้ำกับข้อมูลที่มีอยู่แล้ว (หรือเคยถูกลบไปก่อนหน้านี้)"]);

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
                'birth_date' => ($data['birth_date'] ?? '') !== '' ? $data['birth_date'] : null,
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

    /**
     * Fills only the currently-empty fields on an existing student from this
     * row — e.g. backfilling birth_date for students imported before that
     * column was read. Never overwrites a value that's already set, so a
     * re-import can't silently clobber a manual correction staff made.
     */
    private function fillMissingFields(Student $student, array $data, int $rowNumber): void
    {
        $candidates = [
            'national_id' => isset($data['national_id']) && $data['national_id'] !== '' ? trim((string) $data['national_id']) : null,
            'prefix' => $data['prefix'] ?? null,
            'birth_date' => ($data['birth_date'] ?? '') !== '' ? $data['birth_date'] : null,
            'program' => $data['program'] ?? null,
            'degree_level' => $data['degree_level'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        $changes = [];

        foreach ($candidates as $field => $value) {
            if ($value !== null && blank($student->{$field})) {
                $changes[$field] = $value;
            }
        }

        if ($changes === []) {
            return;
        }

        try {
            $student->update($changes);
        } catch (QueryException $e) {
            // Most likely national_id now collides with a different student.
            $this->recordFailure($rowNumber, ['ไม่สามารถอัปเดตข้อมูลที่ขาดหายไปได้ เนื่องจากข้อมูลใหม่ซ้ำกับนักศึกษาคนอื่น']);

            return;
        }

        ImportLog::whereKey($this->importLogId)->increment('imported_rows');
        ImportLog::whereKey($this->importLogId)->increment('updated_rows');
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
