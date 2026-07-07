<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    private const FIRST_NAMES = [
        'สมชาย', 'สมหญิง', 'วิชัย', 'มานพ', 'สุนีย์', 'อรทัย', 'ประยุทธ', 'กมลชนก',
        'ธนกร', 'ปิยะดา', 'ณัฐพล', 'จิราพร', 'วรากร', 'ศิริพร', 'สุรชัย', 'อัจฉรา',
        'พงศกร', 'เบญจวรรณ', 'ชัยวัฒน์', 'นภัสสร',
    ];

    private const LAST_NAMES = [
        'ใจดี', 'รักเรียน', 'มั่นคง', 'สุขสันต์', 'เจริญพร', 'ทองดี', 'แสงสว่าง',
        'บุญมา', 'ศรีสุข', 'พูลสวัสดิ์', 'วงศ์ษา', 'สายทอง', 'ชัยชนะ', 'เพียรงาน',
    ];

    private const PROGRAMS = [
        'ช่างยนต์', 'ช่างไฟฟ้ากำลัง', 'ช่างกลโรงงาน', 'อิเล็กทรอนิกส์',
        'เทคโนโลยีสารสนเทศ', 'คอมพิวเตอร์ธุรกิจ', 'การบัญชี', 'การตลาด', 'การโรงแรม',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'student_code' => $this->faker->unique()->numerify('##-#####'),
            'national_id' => $this->faker->unique()->numerify('#############'),
            'prefix' => $this->faker->randomElement(['นาย', 'นางสาว']),
            'first_name' => $this->faker->randomElement(self::FIRST_NAMES),
            'last_name' => $this->faker->randomElement(self::LAST_NAMES),
            'program' => $this->faker->randomElement(self::PROGRAMS),
            'degree_level' => $this->faker->randomElement(['ปวช.', 'ปวส.']),
            'phone' => $this->faker->numerify('08########'),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => 'studying',
        ];
    }

    public function graduated(): static
    {
        return $this->state(fn () => [
            'status' => 'graduated',
            'graduated_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function droppedOut(): static
    {
        return $this->state(fn () => ['status' => 'dropped_out']);
    }
}
