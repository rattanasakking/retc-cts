<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">สถิติการใช้งานหน้าแจ้งข้อมูล</h1>
            <p class="text-sm text-base-content/60">
                นักศึกษาเข้ามากรอกข้อมูลด้วยตนเองมากแค่ไหน และติดอยู่ตรงขั้นไหน
                @if ($trackingSince)
                    · เก็บสถิติตั้งแต่ {{ $trackingSince->format('d/m/').($trackingSince->format('Y') + 543) }}
                @endif
            </p>
        </div>

        <select wire:model.live="days" class="select select-bordered select-sm w-full sm:w-48">
            <option value="7">7 วันที่ผ่านมา</option>
            <option value="30">30 วันที่ผ่านมา</option>
            <option value="90">90 วันที่ผ่านมา</option>
            <option value="0">ทั้งหมด</option>
        </select>
    </div>

    {{-- Headline numbers --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ผู้เข้าใช้งาน</p>
                <p class="text-2xl font-bold tabular-nums">{{ number_format($uniqueVisitors) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
                <p class="text-xs text-base-content/50">เปิดหน้าทั้งหมด {{ number_format($visits) }} ครั้ง</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">แจ้งข้อมูลสำเร็จ</p>
                <p class="text-2xl font-bold tabular-nums text-primary">{{ number_format($submitted) }} <span class="text-sm font-normal text-base-content/60">ครั้ง</span></p>
                <p class="text-xs text-base-content/50">{{ $completionRate }}% ของการเปิดหน้า</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ยืนยันตัวตนไม่ผ่าน</p>
                <p class="text-2xl font-bold tabular-nums {{ $verifyFailureRate >= 40 ? 'text-error' : '' }}">{{ $verifyFailureRate }}%</p>
                <p class="text-xs text-base-content/50">{{ number_format($verifyFailed) }} จาก {{ number_format($verifyFailed + $verifySuccess) }} ครั้งที่กดยืนยัน</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ใช้ผ่านมือถือ</p>
                <p class="text-2xl font-bold tabular-nums">{{ $mobileShare }}%</p>
                @if ($busiestHour !== null)
                    <p class="text-xs text-base-content/50">ใช้งานมากสุดช่วง {{ sprintf('%02d.00', $busiestHour) }} น.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Funnel --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">ขั้นตอนที่นักศึกษาผ่าน</h2>
            <p class="text-sm text-base-content/60 -mt-1">ตัวเลขที่หายไปในแต่ละขั้นคือคนที่เลิกกลางทาง</p>

            @php
                $steps = [
                    ['label' => 'เปิดหน้าแจ้งข้อมูล', 'value' => $visits, 'class' => 'bg-primary'],
                    ['label' => 'กดยืนยันตัวตน', 'value' => $verifySuccess + $verifyFailed, 'class' => 'bg-primary/70'],
                    ['label' => 'ยืนยันตัวตนผ่าน', 'value' => $verifySuccess, 'class' => 'bg-primary/50'],
                    ['label' => 'ส่งข้อมูลสำเร็จ', 'value' => $submitted, 'class' => 'bg-success'],
                ];
                $widest = max(1, $visits);
            @endphp

            <div class="space-y-3 mt-3">
                @foreach ($steps as $step)
                    <div>
                        <div class="flex items-baseline justify-between text-sm mb-1">
                            <span>{{ $step['label'] }}</span>
                            <span class="tabular-nums font-semibold">
                                {{ number_format($step['value']) }}
                                <span class="text-xs font-normal text-base-content/50">
                                    ({{ $visits > 0 ? round($step['value'] / $widest * 100) : 0 }}%)
                                </span>
                            </span>
                        </div>
                        <div class="h-3 w-full rounded-full bg-base-200 overflow-hidden">
                            <div class="h-full rounded-full {{ $step['class'] }}" style="width: {{ $visits > 0 ? min(100, $step['value'] / $widest * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($verifyFailureRate >= 40 && ($verifyFailed + $verifySuccess) >= 10)
                <div class="alert bg-warning/10 border border-warning/30 text-sm mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>ยืนยันตัวตนไม่ผ่านเกินครึ่งค่อนข้างมาก มักเกิดจากวันเดือนปีเกิดในระบบว่างหรือไม่ตรงกับที่นักศึกษาจำได้ — ลองตรวจสอบข้อมูลวันเกิดที่นำเข้ามา</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Daily chart --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">การใช้งานรายวัน</h2>
            <div wire:key="usage-chart-{{ $days }}" wire:ignore x-data="usageChart(@js($chart))" x-init="init($el.querySelector('canvas'))" class="mt-2 w-full" style="height: 300px">
                <canvas></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Hour of day --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">ช่วงเวลาที่เข้าใช้งาน</h2>
                <p class="text-sm text-base-content/60 -mt-1">ใช้เลือกเวลาส่งแจ้งเตือนให้ตรงกับตอนที่นักศึกษาเปิดอ่านจริง</p>

                <div class="flex items-end gap-[2px] h-32 mt-4">
                    @foreach ($hourly as $hour => $count)
                        <div class="flex-1 flex flex-col items-center justify-end h-full" title="{{ sprintf('%02d.00', $hour) }} น. — {{ number_format($count) }} ครั้ง">
                            <div class="w-full rounded-t {{ $count > 0 ? 'bg-primary' : 'bg-base-200' }}" style="height: {{ max(2, $count / $hourlyMax * 100) }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between text-xs text-base-content/50 mt-1">
                    <span>00.00</span><span>06.00</span><span>12.00</span><span>18.00</span><span>23.00</span>
                </div>
            </div>
        </div>

        {{-- Coverage --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">ความครอบคลุม</h2>
                <p class="text-sm text-base-content/60 -mt-1">ผู้สำเร็จการศึกษาที่แจ้งข้อมูลด้วยตนเองแล้ว เทียบกับทั้งหมดในระบบ</p>

                <div class="flex items-center gap-6 mt-4">
                    <div class="radial-progress text-primary" style="--value:{{ $graduateCoverage }}; --size:6rem; --thickness:0.6rem" role="progressbar" aria-valuenow="{{ $graduateCoverage }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $graduateCoverage }}%
                    </div>
                    <div class="text-sm space-y-1">
                        <p><span class="font-bold tabular-nums text-lg">{{ number_format($studentsWhoSubmitted) }}</span> คน แจ้งข้อมูลด้วยตนเอง</p>
                        <p class="text-base-content/60">จากผู้สำเร็จการศึกษา {{ number_format($graduates) }} คน</p>
                        <p class="text-xs text-base-content/50">นับเฉพาะที่แจ้งผ่านหน้าสาธารณะ ไม่รวมที่เจ้าหน้าที่กรอกให้หรือนำเข้าจากไฟล์</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest submissions --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-0">
            <h2 class="card-title text-base px-4 pt-4">การแจ้งข้อมูลล่าสุด</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>นักศึกษา</th>
                            <th>อุปกรณ์</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent as $event)
                            <tr wire:key="recent-submission-{{ $event->id }}">
                                <td class="whitespace-nowrap tabular-nums">
                                    {{ $event->created_at->format('d/m/').($event->created_at->format('Y') + 543) }}
                                    {{ $event->created_at->format('H:i') }}
                                </td>
                                <td>
                                    @if ($event->student)
                                        <a href="{{ route('students.show', $event->student) }}" wire:navigate class="link link-hover link-primary">
                                            {{ $event->student->prefix }}{{ $event->student->first_name }} {{ $event->student->last_name }}
                                        </a>
                                        <span class="text-xs text-base-content/50 font-mono ml-1">{{ $event->student->student_code }}</span>
                                    @else
                                        <span class="text-base-content/40">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-sm badge-ghost">{{ $event->is_mobile ? 'มือถือ' : 'คอมพิวเตอร์' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-base-content/60 py-8">ยังไม่มีนักศึกษาแจ้งข้อมูลผ่านหน้าสาธารณะในช่วงนี้</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('usageChart', (payload) => ({
        chart: null,
        init(canvas) {
            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: payload.labels,
                    datasets: [
                        {
                            label: 'เปิดหน้า',
                            data: payload.visits,
                            borderColor: '#2563a8',
                            backgroundColor: '#2563a81a',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'แจ้งข้อมูลสำเร็จ',
                            data: payload.submitted,
                            borderColor: '#4fb3a0',
                            backgroundColor: '#4fb3a01a',
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                },
            });
        },
        destroy() { this.chart?.destroy(); },
    }));
</script>
@endscript
