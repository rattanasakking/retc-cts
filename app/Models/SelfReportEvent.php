<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One step of a graduate's journey through the public self-report form.
 * Written from App\Livewire\Public\CareerStatusSelfReport; read by
 * App\Livewire\Reports\SelfReportUsage.
 */
class SelfReportEvent extends Model
{
    use HasFactory;

    public const VISIT = 'visit';

    public const VERIFY_FAILED = 'verify_failed';

    public const VERIFY_SUCCESS = 'verify_success';

    public const SUBMITTED = 'submitted';

    protected $fillable = [
        'event',
        'student_id',
        'visitor_hash',
        'is_mobile',
    ];

    protected function casts(): array
    {
        return [
            'is_mobile' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Records one step. Never throws — a statistics side-effect must not be
     * able to break the form a student is in the middle of filling in.
     */
    public static function record(string $event, ?int $studentId = null): void
    {
        try {
            $request = request();
            $userAgent = (string) $request?->userAgent();

            static::create([
                'event' => $event,
                'student_id' => $studentId,
                'visitor_hash' => hash('sha256', implode('|', [
                    $request?->ip(),
                    $userAgent,
                    now()->toDateString(),
                    config('app.key'),
                ])),
                'is_mobile' => Str::contains($userAgent, ['Mobile', 'Android', 'iPhone', 'iPad'], ignoreCase: true),
            ]);
        } catch (\Throwable) {
            // ignored on purpose — see the docblock
        }
    }
}
