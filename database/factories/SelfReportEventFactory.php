<?php

namespace Database\Factories;

use App\Models\SelfReportEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SelfReportEvent>
 */
class SelfReportEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event' => SelfReportEvent::VISIT,
            'student_id' => null,
            'visitor_hash' => hash('sha256', $this->faker->uuid()),
            'is_mobile' => $this->faker->boolean(70),
        ];
    }

    public function event(string $event): static
    {
        return $this->state(fn () => ['event' => $event]);
    }

    /** Same person coming back — reuses one visitor hash across events. */
    public function visitor(string $hash): static
    {
        return $this->state(fn () => ['visitor_hash' => $hash]);
    }
}
