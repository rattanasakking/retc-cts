<?php

namespace Database\Factories;

use App\Enums\CareerStatusType;
use App\Models\AcademicYear;
use App\Models\CareerStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CareerStatus>
 */
class CareerStatusFactory extends Factory
{
    private const COMPANIES = [
        'บริษัท ไทยซัมมิท จำกัด', 'บริษัท ซีพี ออลล์ จำกัด (มหาชน)', 'บริษัท เจริญโภคภัณฑ์ จำกัด',
        'บริษัท ปตท. จำกัด (มหาชน)', 'บริษัท เอสซีจี จำกัด', 'ห้างหุ้นส่วนจำกัด รุ่งเรืองการช่าง',
    ];

    private const PROVINCES = [
        'กรุงเทพมหานคร', 'ชลบุรี', 'ระยอง', 'สมุทรปราการ', 'นนทบุรี', 'ปทุมธานี', 'เชียงใหม่', 'ขอนแก่น',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(CareerStatusType::cases());
        $isWorking = in_array($status, [CareerStatusType::Employed, CareerStatusType::Entrepreneur], true);

        return [
            'student_id' => Student::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'status' => $status,
            'company_name' => $isWorking ? $this->faker->randomElement(self::COMPANIES) : null,
            'position' => $isWorking ? $this->faker->jobTitle() : null,
            'monthly_salary' => $isWorking ? $this->faker->numberBetween(120, 350) * 100 : null,
            'employment_type' => $status === CareerStatusType::Employed ? $this->faker->randomElement(['full_time', 'part_time', 'contract']) : null,
            'work_location' => $isWorking ? $this->faker->randomElement(self::PROVINCES) : null,
            'is_related_to_major' => $isWorking ? $this->faker->boolean(70) : null,
            'effective_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'source' => $this->faker->randomElement(['survey', 'manual', 'imported']),
            'is_current' => true,
        ];
    }

    public function status(CareerStatusType $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
