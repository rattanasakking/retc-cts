<?php

namespace App\Imports;

use App\Enums\CareerStatusType;
use App\Imports\Concerns\ImportsStudentRow;
use App\Models\CareerStatus;
use App\Models\ImportLog;
use App\Models\Student;
use App\Support\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Row;

/**
 * Imports the Thai vocational-school "รายงานติดตามภาวะการมีงานทำและศึกษาต่อ"
 * (graduate job/further-study tracking) CSV exported directly from the
 * school's SIS — a fixed-layout report with 4 title/metadata rows before
 * the header row, and Thai column headers that don't slug into anything
 * matchable (Str::slug drops non-Latin text entirely). Rows are read by
 * fixed column position instead of via WithHeadingRow for that reason.
 *
 * Column positions (0-indexed) as exported by this specific report:
 *   0 ชื่อ นามสกุล, 2 ระดับชั้น, 3 รหัสบัตรประชาชน, 6 ปีที่จบ,
 *   8 เลขรหัสนักเรียน, 9 วันเกิด (DD/MM/YYYY, ค.ศ.), 10 อีเมล,
 *   11 โทรศัพท์, 13 สาขาวิชา, 17 ชื่อสถานศึกษาเรียนต่อ,
 *   18 สถานะเรียนตรงสาย ไม่ตรงสาย, 20 สาขาวิชาที่เรียนต่อ,
 *   21 ชื่อสถานที่ทำงาน, 22 ตำแหน่งงาน, 23 เงินเดือน,
 *   24 ทำงานตรงสาย ไม่ตรงสาย
 */
class SchoolJobTrackingImport implements OnEachRow, ShouldQueue, WithChunkReading, WithEvents
{
    use ImportsStudentRow;

    /** 1-indexed: rows 1-4 are report/school title metadata, row 5 is the header row. */
    private const FIRST_DATA_ROW = 6;

    private const COL_FULL_NAME = 0;

    private const COL_DEGREE_LEVEL = 2;

    private const COL_NATIONAL_ID = 3;

    private const COL_GRADUATED_YEAR = 6;

    private const COL_STUDENT_CODE = 8;

    private const COL_BIRTH_DATE = 9;

    private const COL_EMAIL = 10;

    private const COL_PHONE = 11;

    private const COL_PROGRAM = 13;

    private const COL_FURTHER_STUDY_INSTITUTION = 17;

    private const COL_FURTHER_STUDY_RELEVANCE = 18;

    private const COL_FURTHER_STUDY_MAJOR = 20;

    private const COL_WORKPLACE = 21;

    private const COL_POSITION = 22;

    private const COL_SALARY_RANGE = 23;

    private const COL_WORK_RELEVANCE = 24;

    public function __construct(private readonly int $importLogId, private readonly bool $updateExisting = false)
    {
    }

    public function onRow(Row $row): void
    {
        // Unlike WithHeadingRow imports (where getIndex() counts data rows
        // after the header, 0-based), a plain OnEachRow import's getIndex()
        // is already the file's literal 1-based line number — no +1 needed,
        // and it doubles as an accurate line number in failure messages.
        $rowNumber = $row->getIndex();

        if ($rowNumber < self::FIRST_DATA_ROW) {
            return;
        }

        $cells = $row->toArray();
        [$firstName, $lastName] = $this->splitName($this->cell($cells, self::COL_FULL_NAME));

        $student = $this->validateAndCreateStudent($rowNumber, [
            'student_code' => $this->cell($cells, self::COL_STUDENT_CODE),
            'national_id' => $this->cell($cells, self::COL_NATIONAL_ID),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $this->parseThaiReportDate($this->cell($cells, self::COL_BIRTH_DATE)),
            'academic_year' => $this->cell($cells, self::COL_GRADUATED_YEAR),
            'program' => $this->cell($cells, self::COL_PROGRAM) ?: null,
            'degree_level' => $this->cell($cells, self::COL_DEGREE_LEVEL) ?: null,
            'phone' => $this->cell($cells, self::COL_PHONE) ?: null,
            'email' => $this->cell($cells, self::COL_EMAIL) ?: null,
            'status' => 'graduated',
        ]);

        if ($student) {
            $this->createCareerStatus($student, $cells);
        }
    }

    private function createCareerStatus(Student $student, array $cells): void
    {
        $workplace = $this->cell($cells, self::COL_WORKPLACE);
        $furtherStudyInstitution = $this->cell($cells, self::COL_FURTHER_STUDY_INSTITUTION);

        if ($workplace === '' && $furtherStudyInstitution === '') {
            return;
        }

        // Suppress CareerStatusObserver's per-record admin notification —
        // a bulk import creating hundreds of rows would otherwise flood
        // every admin's email/LINE with one message per student.
        CareerStatus::withoutEvents(function () use ($student, $cells, $workplace, $furtherStudyInstitution) {
            if ($workplace !== '') {
                CareerStatus::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $student->academic_year_id,
                    'status' => CareerStatusType::Employed,
                    'company_name' => $workplace,
                    'position' => $this->cell($cells, self::COL_POSITION) ?: null,
                    'monthly_salary' => $this->parseSalaryRange($this->cell($cells, self::COL_SALARY_RANGE)),
                    'is_related_to_major' => $this->cell($cells, self::COL_WORK_RELEVANCE) === 'ตรง',
                    'effective_date' => now(),
                    'source' => 'imported',
                    'is_current' => true,
                ]);

                return;
            }

            $major = $this->cell($cells, self::COL_FURTHER_STUDY_MAJOR);

            CareerStatus::create([
                'student_id' => $student->id,
                'academic_year_id' => $student->academic_year_id,
                'status' => CareerStatusType::FurtherStudy,
                'institution_name' => $furtherStudyInstitution !== '' ? $furtherStudyInstitution : null,
                'is_related_to_major' => $this->cell($cells, self::COL_FURTHER_STUDY_RELEVANCE) === 'ตรง',
                'effective_date' => now(),
                'source' => 'imported',
                'is_current' => true,
                'notes' => trim("ศึกษาต่อที่ {$furtherStudyInstitution}".($major !== '' ? " สาขา {$major}" : '')),
            ]);
        });
    }

    private function cell(array $cells, int $index): string
    {
        return trim((string) ($cells[$index] ?? ''));
    }

    /**
     * The report's date columns are DD/MM/YYYY in ค.ศ. (Gregorian/AD), not
     * พ.ศ. — despite everything else in this report being Thai-formatted,
     * the school's SIS exports this one column as a plain AD date. Convert
     * to Y-m-d for storage; anything unparseable is dropped rather than
     * failing the whole row over a non-critical field.
     */
    private function parseThaiReportDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: string} [first_name, last_name] — Thai
     *      names in this report are "FirstName LastName" separated by a
     *      single space, so only the first space is a real split point.
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', $fullName, 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /**
     * Salary is exported as a bracket like "9,001 - 15,000", not a single
     * figure — average the two bounds as a representative monthly_salary.
     */
    private function parseSalaryRange(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        preg_match_all('/[\d,]+/', $value, $matches);
        $numbers = array_map(fn ($n) => (float) str_replace(',', '', $n), $matches[0] ?? []);

        return $numbers === [] ? null : array_sum($numbers) / count($numbers);
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
                    description: "นำเข้าข้อมูลนักศึกษาจากรายงานติดตามภาวะการมีงานทำ {$importLog->file_name} (สำเร็จ {$importLog->imported_rows} แถว, ล้มเหลว {$importLog->failed_rows} แถว)",
                    userId: $importLog->user_id,
                );
            },
        ];
    }
}
