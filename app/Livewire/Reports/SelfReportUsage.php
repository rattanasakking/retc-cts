<?php

namespace App\Livewire\Reports;

use App\Models\SelfReportEvent;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('สถิติการใช้งานหน้าแจ้งข้อมูล')]
class SelfReportUsage extends Component
{
    /** ย้อนหลังกี่วัน (0 = ตั้งแต่เริ่มเก็บสถิติ) */
    public int $days = 30;

    private function since(): ?Carbon
    {
        return $this->days > 0 ? now()->subDays($this->days)->startOfDay() : null;
    }

    private function eventQuery(?string $event = null)
    {
        return SelfReportEvent::query()
            ->when($event, fn ($query) => $query->where('event', $event))
            ->when($this->since(), fn ($query, $since) => $query->where('created_at', '>=', $since));
    }

    /**
     * จำนวนต่อวันของแต่ละขั้น เอาไว้วาดกราฟ — เติมวันที่ไม่มีใครเข้าใช้งาน
     * ให้เป็น 0 ด้วย ไม่งั้นกราฟจะกระโดดข้ามวันที่เงียบไปเลย
     */
    private function dailySeries(): array
    {
        $days = $this->days > 0 ? $this->days : 90;
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = SelfReportEvent::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, event, count(*) as total')
            ->groupBy('day', 'event')
            ->get();

        $labels = [];
        $visits = [];
        $submitted = [];

        for ($day = $start->copy(); $day <= now(); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('d/m');

            $visits[] = (int) ($rows->first(fn ($row) => $row->day === $key && $row->event === SelfReportEvent::VISIT)?->total ?? 0);
            $submitted[] = (int) ($rows->first(fn ($row) => $row->day === $key && $row->event === SelfReportEvent::SUBMITTED)?->total ?? 0);
        }

        return ['labels' => $labels, 'visits' => $visits, 'submitted' => $submitted];
    }

    /**
     * ช่วงเวลาของวันที่มีการใช้งานมากที่สุด — ใช้ตัดสินใจว่าจะส่งข้อความ
     * แจ้งเตือนนักศึกษาตอนไหนถึงจะมีคนเปิดอ่านจริง
     */
    private function hourlySeries(): array
    {
        $counts = array_fill(0, 24, 0);

        $rows = $this->eventQuery(SelfReportEvent::VISIT)
            ->selectRaw('created_at')
            ->get();

        foreach ($rows as $row) {
            $counts[(int) $row->created_at->format('G')]++;
        }

        return $counts;
    }

    public function render()
    {
        $visits = $this->eventQuery(SelfReportEvent::VISIT)->count();
        $uniqueVisitors = $this->eventQuery(SelfReportEvent::VISIT)->distinct()->count('visitor_hash');
        $verifyFailed = $this->eventQuery(SelfReportEvent::VERIFY_FAILED)->count();
        $verifySuccess = $this->eventQuery(SelfReportEvent::VERIFY_SUCCESS)->count();
        $submitted = $this->eventQuery(SelfReportEvent::SUBMITTED)->count();

        $mobileVisits = $this->eventQuery(SelfReportEvent::VISIT)->where('is_mobile', true)->count();

        $studentsWhoSubmitted = $this->eventQuery(SelfReportEvent::SUBMITTED)->distinct()->count('student_id');
        $graduates = Student::where('status', 'graduated')->count();

        $recent = SelfReportEvent::query()
            ->with('student')
            ->where('event', SelfReportEvent::SUBMITTED)
            ->latest('id')
            ->limit(10)
            ->get();

        $hourly = $this->hourlySeries();
        $busiestHour = $hourly === array_fill(0, 24, 0) ? null : array_search(max($hourly), $hourly, true);

        return view('livewire.reports.self-report-usage', [
            'visits' => $visits,
            'uniqueVisitors' => $uniqueVisitors,
            'verifyFailed' => $verifyFailed,
            'verifySuccess' => $verifySuccess,
            'submitted' => $submitted,
            'mobileShare' => $visits > 0 ? (int) round($mobileVisits / $visits * 100) : 0,
            'completionRate' => $visits > 0 ? (int) round($submitted / $visits * 100) : 0,
            'verifyFailureRate' => ($verifySuccess + $verifyFailed) > 0
                ? (int) round($verifyFailed / ($verifySuccess + $verifyFailed) * 100)
                : 0,
            'studentsWhoSubmitted' => $studentsWhoSubmitted,
            'graduateCoverage' => $graduates > 0 ? (int) round($studentsWhoSubmitted / $graduates * 100) : 0,
            'graduates' => $graduates,
            'busiestHour' => $busiestHour,
            'hourly' => $hourly,
            'hourlyMax' => max(1, max($hourly)),
            'chart' => $this->dailySeries(),
            'recent' => $recent,
            'trackingSince' => SelfReportEvent::min('created_at')
                ? Carbon::parse(SelfReportEvent::min('created_at'))
                : null,
        ]);
    }
}
