<?php

namespace Database\Seeders;

use App\Enums\CareerStatusType;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use Illuminate\Database\Seeder;

class CareerTrackingSeeder extends Seeder
{
    /**
     * Seed academic years, students, and their career-tracking survey responses.
     */
    public function run(): void
    {
        $y2567 = AcademicYear::factory()->create(['year' => 2567, 'is_active' => false]);
        $y2568 = AcademicYear::factory()->create(['year' => 2568, 'is_active' => false]);
        $y2569 = AcademicYear::factory()->create(['year' => 2569, 'is_active' => true]);

        // Past years: fully graduated cohorts, high survey response rate.
        $this->seedCohort($y2567, graduates: 120, respondents: 110, distribution: [
            CareerStatusType::Employed->value => 68,
            CareerStatusType::FurtherStudy->value => 20,
            CareerStatusType::Unemployed->value => 13,
            CareerStatusType::MilitaryService->value => 5,
            CareerStatusType::Entrepreneur->value => 3,
            CareerStatusType::Other->value => 1,
        ]);

        $this->seedCohort($y2568, graduates: 140, respondents: 126, distribution: [
            CareerStatusType::Employed->value => 78,
            CareerStatusType::FurtherStudy->value => 23,
            CareerStatusType::Unemployed->value => 15,
            CareerStatusType::MilitaryService->value => 6,
            CareerStatusType::Entrepreneur->value => 3,
            CareerStatusType::Other->value => 1,
        ]);

        // Active year: survey still in progress — partial response rate.
        $this->seedCohort($y2569, graduates: 160, respondents: 72, distribution: [
            CareerStatusType::Employed->value => 40,
            CareerStatusType::FurtherStudy->value => 15,
            CareerStatusType::Unemployed->value => 10,
            CareerStatusType::MilitaryService->value => 4,
            CareerStatusType::Entrepreneur->value => 2,
            CareerStatusType::Other->value => 1,
        ]);

        // The active year also has current students who haven't graduated yet.
        Student::factory()->count(35)->create(['academic_year_id' => $y2569->id, 'status' => 'studying']);
        Student::factory()->droppedOut()->count(8)->create(['academic_year_id' => $y2569->id]);
    }

    /**
     * @param  array<string, int>  $distribution
     */
    private function seedCohort(AcademicYear $year, int $graduates, int $respondents, array $distribution): void
    {
        $students = Student::factory()
            ->graduated()
            ->count($graduates)
            ->create(['academic_year_id' => $year->id])
            ->shuffle();

        $responders = $students->take($respondents);
        $cursor = 0;

        foreach ($distribution as $status => $count) {
            foreach ($responders->slice($cursor, $count) as $student) {
                CareerStatus::factory()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'status' => $status,
                ]);
            }

            $cursor += $count;
        }
    }
}
