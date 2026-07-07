<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2565, 2570);

        return [
            'year' => $year,
            'start_date' => "{$year}-06-01",
            'end_date' => ($year + 1).'-05-31',
            'is_active' => false,
        ];
    }
}
