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

    {{-- Stat cards: icon tile top-left, share of the cohort as a pill top-right,
         label, then the figure — the Skylearn stat-card pattern. --}}
    @php
        $statCards = [
            ['label' => 'ผู้สำเร็จการศึกษา', 'value' => $stats['graduates'], 'pill' => null, 'tone' => 'text-base-content',
             'icon' => 'M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5'],
            ['label' => 'ผู้ตอบแบบสอบถาม', 'value' => $stats['respondents'], 'pill' => $rates['response'], 'tone' => 'text-secondary',
             'icon' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z'],
            ['label' => 'มีงานทำ', 'value' => $stats['employed'], 'pill' => $rates['employed'], 'tone' => 'text-primary',
             'icon' => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0'],
            ['label' => 'ศึกษาต่อ', 'value' => $stats['further_study'], 'pill' => null, 'tone' => 'text-success',
             'icon' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
            ['label' => 'ว่างงาน', 'value' => $stats['unemployed'], 'pill' => null, 'tone' => 'text-error',
             'icon' => 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z'],
            ['label' => 'อื่นๆ', 'value' => $stats['other'], 'pill' => null, 'tone' => 'text-base-content/70',
             'icon' => 'M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm6 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z'],
        ];
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach ($statCards as $card)
            <div class="card bg-base-100">
                <div class="card-body p-4 gap-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="icon-chip">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                            </svg>
                        </span>
                        @if ($card['pill'] !== null)
                            <span @class(['delta-pill', 'is-up' => $card['pill'] >= 50, 'is-flat' => $card['pill'] < 50])>{{ $card['pill'] }}%</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/60">{{ $card['label'] }}</p>
                        <p class="kpi {{ $card['tone'] }}">{{ number_format($card['value']) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Extra metrics --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <p class="text-xs text-base-content/60">สัดส่วนงานตรงสาย</p>
                <p class="text-lg font-bold tabular-nums">{{ $metrics['related_to_major_rate'] }}%</p>
            </div>
        </div>
    </div>

    {{-- Top 5 rankings --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">สถานศึกษาที่ศึกษาต่อมากที่สุด</h2>
                <ol class="mt-1 space-y-2">
                    @forelse ($topInstitutions as $i => $row)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="badge badge-sm badge-ghost shrink-0">{{ $i + 1 }}</span>
                                <span class="truncate">{{ $row->name }}</span>
                            </span>
                            <span class="font-semibold tabular-nums shrink-0">{{ number_format($row->total) }} คน</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/50">ยังไม่มีข้อมูล</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">บริษัทที่มีนักศึกษาทำงานมากที่สุด</h2>
                <ol class="mt-1 space-y-2">
                    @forelse ($topCompanies as $i => $row)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="badge badge-sm badge-ghost shrink-0">{{ $i + 1 }}</span>
                                <span class="truncate">{{ $row->name }}</span>
                            </span>
                            <span class="font-semibold tabular-nums shrink-0">{{ number_format($row->total) }} คน</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/50">ยังไม่มีข้อมูล</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <h2 class="card-title text-base">จังหวัดที่ทำงาน/ศึกษาต่อมากที่สุด</h2>
                <ol class="mt-1 space-y-2">
                    @forelse ($topProvinces as $i => $row)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="badge badge-sm badge-ghost shrink-0">{{ $i + 1 }}</span>
                                <span class="truncate">{{ $row->name }}</span>
                            </span>
                            <span class="font-semibold tabular-nums shrink-0">{{ number_format($row->total) }} คน</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/50">ยังไม่มีข้อมูล</li>
                    @endforelse
                </ol>
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

        <div class="card bg-base-100 shadow lg:col-span-2">
            <div class="card-body">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <h2 class="card-title text-base">แผนที่การกระจายตัวตามจังหวัด</h2>
                    <div class="flex items-center gap-3 text-xs text-base-content/60">
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full inline-block" style="background:#2563a8"></span>มีงานทำเป็นส่วนใหญ่</span>
                        <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full inline-block" style="background:#4fb3a0"></span>ศึกษาต่อเป็นส่วนใหญ่</span>
                    </div>
                </div>
                @if (empty($provinceMap))
                    <p class="text-sm text-base-content/50 mt-4">ยังไม่มีข้อมูลจังหวัดที่ทำงาน/ศึกษาต่อสำหรับตัวกรองนี้</p>
                @else
                    <div wire:key="map-{{ $filterKey }}" wire:ignore x-data="provinceMap(@js($provinceMap))" x-init="init($el)" class="mt-2 w-full rounded-box overflow-hidden" style="height: 420px"></div>
                @endif
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
                        { label: 'มีงานทำ', data: payload.employed, backgroundColor: '#3b82f6' },
                        { label: 'ว่างงาน', data: payload.unemployed, backgroundColor: '#b5484a' },
                        { label: 'ศึกษาต่อ', data: payload.further_study, backgroundColor: '#22c55e' },
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
                            borderColor: '#3b82f6',
                            backgroundColor: '#3b82f61f',
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: 'อัตราการมีงานทำ (%)',
                            data: payload.employed_rate,
                            borderColor: '#22c55e',
                            backgroundColor: '#22c55e1f',
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

    Alpine.data('provinceMap', (payload) => ({
        map: null,

        init(el) {
            this.map = L.map(el, { scrollWheelZoom: false }).setView([13.7563, 100.5018], 6);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
                maxZoom: 18,
            }).addTo(this.map);

            const maxTotal = Math.max(...payload.map((p) => p.total));
            const bounds = [];

            payload.forEach((p) => {
                const radius = 8 + (p.total / maxTotal) * 22;
                const color = p.employed >= p.further_study ? '#3b82f6' : '#22c55e';

                L.circleMarker([p.lat, p.lng], {
                    radius,
                    color,
                    weight: 1,
                    fillColor: color,
                    fillOpacity: 0.55,
                })
                    .addTo(this.map)
                    .bindPopup(`<strong>${p.name}</strong><br>มีงานทำ: ${p.employed} คน<br>ศึกษาต่อ: ${p.further_study} คน`);

                bounds.push([p.lat, p.lng]);
            });

            if (bounds.length) this.map.fitBounds(bounds, { padding: [24, 24], maxZoom: 8 });
        },

        destroy() { this.map?.remove(); },
    }));
</script>
@endscript
