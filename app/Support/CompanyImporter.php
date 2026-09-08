<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads a DBD open-data juristic-person CSV into the companies table.
 *
 * DBD publishes these per dataset and the header wording is not stable
 * between them, so columns are matched by a list of aliases rather than by
 * position — and if the name column cannot be found at all the caller is told
 * which headers the file actually had, which is the only way to fix it
 * without opening the file by hand.
 */
class CompanyImporter
{
    /** Header aliases seen across the DBD datasets. */
    private const COLUMNS = [
        'name' => ['ชื่อนิติบุคคล', 'ชื่อนิติบุคคลภาษาไทย', 'ชื่อ', 'juristicnamth', 'juristicname', 'name'],
        'juristic_id' => ['เลขทะเบียนนิติบุคคล', 'เลขทะเบียน13หลัก', 'เลขทะเบียน', 'juristicid', 'juristicno'],
        'type' => ['ประเภทนิติบุคคล', 'ประเภท', 'juristictype'],
        'status' => ['สถานะนิติบุคคล', 'สถานะ', 'juristicstatus'],
        'province' => ['จังหวัด', 'จังหวัดที่ตั้ง', 'province'],
        'district' => ['อำเภอ', 'อำเภอ/เขต', 'อำเภอที่ตั้ง', 'district', 'amphur'],
    ];

    /**
     * @param  string|null  $onlyProvince  keep just one province — the whole
     *                                     country is far more rows than a college needs
     * @return array{imported: int, updated: int, skipped: int, headers: array<int, string>}
     */
    public function import(string $path, ?string $onlyProvince = null): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('เปิดไฟล์ไม่ได้: '.$path);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            throw new RuntimeException('ไฟล์ว่างเปล่า');
        }

        // DBD exports are UTF-8 with a BOM often enough to be worth stripping.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);

        $map = $this->mapColumns($header);

        if (! isset($map['name'])) {
            fclose($handle);

            throw new RuntimeException(
                'ไม่พบคอลัมน์ชื่อนิติบุคคลในไฟล์ — คอลัมน์ที่พบคือ: '.implode(', ', array_filter($header))
            );
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            $record = $this->buildRecord($row, $map);

            if ($record === null || ($onlyProvince && $record['province'] !== $onlyProvince)) {
                $skipped++;

                continue;
            }

            $batch[] = $record;

            if (count($batch) >= 500) {
                [$new, $touched] = $this->flush($batch);
                $imported += $new;
                $updated += $touched;
                $batch = [];
            }
        }

        if ($batch !== []) {
            [$new, $touched] = $this->flush($batch);
            $imported += $new;
            $updated += $touched;
        }

        fclose($handle);

        return ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'headers' => $header];
    }

    /**
     * Names already recorded on career statuses, so the suggestion list starts
     * out useful even before any DBD file is loaded.
     */
    public function importFromExistingCareerStatuses(): int
    {
        $names = DB::table('career_statuses')
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->distinct()
            ->pluck('company_name');

        $added = 0;

        foreach ($names as $name) {
            $name = trim((string) $name);

            if ($name === '' || mb_strlen($name) > 255) {
                continue;
            }

            if (Company::where('name', $name)->doesntExist()) {
                Company::create(['name' => $name, 'source' => 'entered']);
                $added++;
            }
        }

        return $added;
    }

    /**
     * @param  array<int, string|null>  $header
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $normalised = $this->normalise((string) $label);

            foreach (self::COLUMNS as $field => $aliases) {
                if (isset($map[$field])) {
                    continue;
                }

                foreach ($aliases as $alias) {
                    if ($normalised === $this->normalise($alias)) {
                        $map[$field] = $index;

                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    private function normalise(string $label): string
    {
        return str_replace([' ', "\t", '_', '-', '.'], '', mb_strtolower(trim($label)));
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $map
     * @return array<string, string|null>|null
     */
    private function buildRecord(array $row, array $map): ?array
    {
        $value = function (string $field, int $limit = 255) use ($row, $map): ?string {
            if (! isset($map[$field])) {
                return null;
            }

            $raw = trim((string) ($row[$map[$field]] ?? ''));

            return $raw !== '' ? mb_substr($raw, 0, $limit) : null;
        };

        $name = $value('name');

        if ($name === null) {
            return null;
        }

        return [
            'name' => $name,
            'juristic_id' => $value('juristic_id', 20),
            'type' => $value('type', 100),
            'status' => $value('status', 100),
            'province' => $value('province', 100),
            'district' => $value('district', 100),
            'source' => 'dbd',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  array<int, array<string, string|null>>  $batch
     * @return array{0: int, 1: int} [inserted, updated]
     */
    private function flush(array $batch): array
    {
        // Rows without a juristic id cannot be deduplicated against DBD's own
        // key, so they are matched on the name instead.
        $withId = array_values(array_filter($batch, fn ($row) => $row['juristic_id'] !== null));
        $withoutId = array_values(array_filter($batch, fn ($row) => $row['juristic_id'] === null));

        $inserted = 0;
        $updated = 0;

        if ($withId !== []) {
            $existing = Company::whereIn('juristic_id', array_column($withId, 'juristic_id'))->count();
            Company::upsert($withId, ['juristic_id'], ['name', 'type', 'status', 'province', 'district', 'source', 'updated_at']);
            $inserted += count($withId) - $existing;
            $updated += $existing;
        }

        foreach ($withoutId as $row) {
            if (Company::where('name', $row['name'])->doesntExist()) {
                Company::create($row);
                $inserted++;
            } else {
                $updated++;
            }
        }

        return [$inserted, $updated];
    }
}
