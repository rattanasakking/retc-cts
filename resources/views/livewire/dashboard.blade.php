<div class="space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">ภาพรวมการติดตามภาวะการมีงานทำ</h1>
            <p class="text-sm text-base-content/60">สรุปข้อมูลผู้จบการศึกษาและผลการติดตามภาวะการทำงาน</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 w-full lg:w-auto">
            <label class="form-control">
                <span class="label-text text-xs mb-1">ปีการศึกษา</span>
                <select wire:model.live="selectedYearId" class="select select-bordered select-sm">
                    @foreach ($years as $y)
                        <option value="{{ $y->id }}">ปีการศึกษา {{ $y->year }} @if ($y->is_active) (ปัจจุบัน) @endif</option>
                    @endforeach
                </select>
            </label>

            <label class="form-control">
                <span class="label-text text-xs mb-1">แผนกวิชา</span>
                <select wire:model.live="selectedProgram" class="select select-bordered select-sm">
                    <option value="">ทุกแผนก</option>
                    @foreach ($programs as $program)
                        <option value="{{ $program }}">{{ $program }}</option>
                    @endforeach
                </select>
            </label>

            <label class="form-control">
                <span class="label-text text-xs mb-1">ระดับการศึกษา</span>
                <select wire:model.live="selectedDegreeLevel" class="select select-bordered select-sm">
                    <option value="">ทุกระดับ</option>
                    @foreach ($degreeLevels as $level)
                        <option value="{{ $level }}">{{ $level }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>

    @if ($selectedProgram || $selectedDegreeLevel)
        <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs">ล้างตัวกรองแผนก/ระดับ</button>
    @endif

    {{-- 6 stat cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight">{{ number_format($stats['graduates']) }}</p>
                <p class="text-xs text-base-content/60">ผู้สำเร็จการศึกษา</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight text-secondary">{{ number_format($stats['respondents']) }}</p>
                <p class="text-xs text-base-content/60">ผู้ตอบแบบสอบถาม</p>
                <p class="text-xs text-secondary font-medium">{{ $rates['response'] }}%</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight text-primary">{{ number_format($stats['employed']) }}</p>
                <p class="text-xs text-base-content/60">มีงานทำ</p>
                <p class="text-xs text-primary font-medium">{{ $rates['employed'] }}%</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight text-accent">{{ number_format($stats['further_study']) }}</p>
                <p class="text-xs text-base-content/60">ศึกษาต่อ</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight text-error">{{ number_format($stats['unemployed']) }}</p>
                <p class="text-xs text-base-content/60">ว่างงาน</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4 gap-1">
                <p class="text-2xl font-bold tabular-nums leading-tight text-base-content/70">{{ number_format($stats['other']) }}</p>
                <p class="text-xs text-base-content/60">อื่นๆ</p>
            </div>
        </div>
    </div>

    {{-- Extra metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/60">เงินเดือนเฉลี่ย</p>
                <p class="text-lg font-bold tabular-nums">
                    {{ $metrics['avg_salary'] ? number_format($metrics['avg_salary'], 0).' บาท' : '—' }}
                </p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/60">จังหวัดที่ทำงานมากที่สุด</p>
                <p class="text-lg font-bold truncate">{{ $metrics['top_province'] ?: '—' }}</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/60">บริษัทที่มีนักศึกษาทำงานมากที่สุด</p>
                <p class="text-lg font-bold truncate">{{ $metrics['top_company'] ?: '—' }}</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/60">สัดส่วนงานตรงสาย</p>
                <p class="text-lg font-bold tabular-nums">{{ $metrics['related_to_major_rate'] }}%</p>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">สัดส่วนภาวะการทำงาน (Doughnut)</h2>
                <div wire:key="doughnut-{{ $filterKey }}" wire:ignore x-data="doughnutChart(@js($statusChart))" x-init="init($el.querySelector('canvas'))" class="mt-2 w-full" style="height: 280px">
                    <canvas></canvas>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">สัดส่วนงานตรงสาย (Pie)</h2>
                <div wire:key="pie-{{ $filterKey }}" wire:ignore x-data="pieChart(@js($relatedChart))" x-init="init($el.querySelector('canvas'))" class="mt-2 w-full" style="height: 280px">
                    <canvas></canvas>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">เปรียบเทียบตามแผนกวิชา (Bar)</h2>
                <div class="overflow-x-auto">
                    <div wire:key="bar-{{ $filterKey }}" wire:ignore x-data="barChart(@js($departmentChart))" x-init="init($el.querySelector('canvas'))" class="mt-2" style="height: 300px; min-width: 480px">
                        <canvas></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">แนวโน้มอัตรารายปี (Line)</h2>
                <div wire:ignore x-data="lineChart(@js($trendChart))" x-init="init($el.querySelector('canvas'))" class="mt-2 w-full" style="height: 300px">
                    <canvas></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('doughnutChart', (payload) => ({
        chart: null,
        init(canvas) {
            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: payload.labels,
                    datasets: [{ data: payload.data, backgroundColor: payload.colors, borderWidth: 0 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
                },
            });
        },
        destroy() { this.chart?.destroy(); },
    }));

    Alpine.data('pieChart', (payload) => ({
        chart: null,
        init(canvas) {
            this.chart = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: payload.labels,
                    datasets: [{ data: payload.data, backgroundColor: payload.colors, borderWidth: 0 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } } },
                },
            });
        },
        destroy() { this.chart?.destroy(); },
    }));

    Alpine.data('barChart', (payload) => ({
        chart: null,
        init(canvas) {
            this.chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: payload.labels,
                    datasets: [
                        { label: 'มีงานทำ', data: payload.employed, backgroundColor: '#2563a8' },
                        { label: 'ว่างงาน', data: payload.unemployed, backgroundColor: '#b5484a' },
                        { label: 'ศึกษาต่อ', data: payload.further_study, backgroundColor: '#4fb3a0' },
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

    Alpine.data('lineChart', (payload) => ({
        chart: null,
        init(canvas) {
            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: payload.labels,
                    datasets: [
                        {
                            label: 'อัตราการตอบแบบสอบถาม (%)',
                            data: payload.response_rate,
                            borderColor: '#2563a8',
                            backgroundColor: '#2563a81a',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'อัตราการมีงานทำ (%)',
                            data: payload.employed_rate,
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
                    scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } },
                },
            });
        },
        destroy() { this.chart?.destroy(); },
    }));
</script>
@endscript
