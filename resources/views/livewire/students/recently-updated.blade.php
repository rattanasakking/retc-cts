<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">ข้อมูลนักศึกษาที่ปรับปรุงล่าสุด</h1>
            <p class="text-sm text-base-content/60">รายชื่อนักศึกษาเรียงตามเวลาที่ข้อมูลถูกแก้ไขล่าสุด ทั้งข้อมูลประวัตินักศึกษาและภาวะการมีงานทำ</p>
        </div>
        <a href="{{ route('students.index') }}" wire:navigate class="btn btn-outline btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            กลับไปหน้ารายชื่อนักศึกษา
        </a>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ปรับปรุงใน 24 ชั่วโมงที่ผ่านมา</p>
                <p class="text-2xl font-bold">{{ number_format($updatedTodayCount) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ปรับปรุงใน 7 วันที่ผ่านมา</p>
                <p class="text-2xl font-bold">{{ number_format($updatedThisWeekCount) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ปรับปรุงใน 30 วันที่ผ่านมา</p>
                <p class="text-2xl font-bold">{{ number_format($updatedThisMonthCount) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
            </div>
        </div>
    </div>

    {{-- Search & filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <label class="input input-bordered flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="ค้นหาชื่อ, รหัสนักศึกษา, เลขบัตรประชาชน..."
                        class="grow"
                    >
                </label>

                <select wire:model.live="days" class="select select-bordered">
                    <option value="1">ภายใน 24 ชั่วโมง</option>
                    <option value="7">ภายใน 7 วัน</option>
                    <option value="30">ภายใน 30 วัน</option>
                    <option value="90">ภายใน 90 วัน</option>
                    <option value="0">ทุกช่วงเวลา</option>
                </select>

                <select wire:model.live="filterAcademicYearId" class="select select-bordered">
                    <option value="">ทุกปีการศึกษา</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterSource" class="select select-bordered">
                    <option value="">ทุกประเภทการปรับปรุง</option>
                    <option value="student">ข้อมูลนักศึกษา</option>
                    <option value="career_status">ภาวะการมีงานทำ</option>
                </select>
            </div>

            @if ($search || $filterAcademicYearId || $filterSource || $days !== 30)
                <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs mt-2 w-fit">ล้างตัวกรองทั้งหมด</button>
            @endif
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="card bg-base-100 shadow hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสนักศึกษา</th>
                        <th>ชื่อ-สกุล</th>
                        <th>ปีการศึกษา</th>
                        <th>ปรับปรุงล่าสุด</th>
                        <th>ประเภท</th>
                        <th>ผู้แก้ไขล่าสุด</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        @php
                            $fromCareer = $student->career_updated_at && $student->career_updated_at->gt($student->updated_at);
                            $log = $latestLogs[$student->id] ?? null;
                        @endphp
                        <tr wire:key="recently-updated-row-{{ $student->id }}">
                            <td class="font-mono text-sm">
                                <button type="button" wire:click="openDetail({{ $student->id }})" class="link link-hover link-primary">{{ $student->student_code }}</button>
                            </td>
                            <td>
                                <button type="button" wire:click="openDetail({{ $student->id }})" class="link link-hover link-primary text-left">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</button>
                            </td>
                            <td>{{ $student->academicYear?->year }}</td>
                            <td class="whitespace-nowrap">
                                <p>{{ $student->last_updated_at->format('d/m/').($student->last_updated_at->format('Y') + 543) }} {{ $student->last_updated_at->format('H:i') }}</p>
                                <p class="text-xs text-base-content/50">{{ $student->last_updated_human }}</p>
                            </td>
                            <td>
                                <span @class(['badge badge-sm', 'badge-warning' => $fromCareer, 'badge-ghost' => ! $fromCareer])>
                                    {{ $fromCareer ? 'ภาวะการมีงานทำ' : 'ข้อมูลนักศึกษา' }}
                                </span>
                            </td>
                            <td class="text-sm">
                                @if ($log)
                                    <p>{{ $log->user?->name ?? 'ระบบ' }}</p>
                                    <p class="text-xs text-base-content/50">{{ $log->action->label() }} — {{ $log->module }}</p>
                                @else
                                    <span class="text-base-content/40">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-base-content/60 py-8">ไม่พบข้อมูลนักศึกษาที่ปรับปรุงในช่วงเวลาที่เลือก</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($students as $student)
            @php
                $fromCareer = $student->career_updated_at && $student->career_updated_at->gt($student->updated_at);
                $log = $latestLogs[$student->id] ?? null;
            @endphp
            <div class="card bg-base-100 shadow" wire:key="recently-updated-card-{{ $student->id }}">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <button type="button" wire:click="openDetail({{ $student->id }})" class="text-left">
                            <p class="font-semibold link link-hover link-primary">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</p>
                            <p class="text-xs text-base-content/60 font-mono">{{ $student->student_code }}</p>
                        </button>
                        <span @class(['badge badge-sm shrink-0', 'badge-warning' => $fromCareer, 'badge-ghost' => ! $fromCareer])>
                            {{ $fromCareer ? 'ภาวะการมีงานทำ' : 'ข้อมูลนักศึกษา' }}
                        </span>
                    </div>
                    <div class="text-sm text-base-content/70 grid grid-cols-2 gap-1">
                        <span>ปีการศึกษา {{ $student->academicYear?->year }}</span>
                        <span class="text-right">{{ $student->last_updated_human }}</span>
                    </div>
                    <p class="text-xs text-base-content/50">
                        ปรับปรุง {{ $student->last_updated_at->format('d/m/').($student->last_updated_at->format('Y') + 543) }} {{ $student->last_updated_at->format('H:i') }}
                        @if ($log)
                            โดย {{ $log->user?->name ?? 'ระบบ' }}
                        @endif
                    </p>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center text-base-content/60 py-8">ไม่พบข้อมูลนักศึกษาที่ปรับปรุงในช่วงเวลาที่เลือก</div>
            </div>
        @endforelse
    </div>

    <div>{{ $students->links() }}</div>

    {{-- Student detail popup --}}
    <div class="modal {{ $viewingStudent ? 'modal-open' : '' }}" role="dialog" aria-modal="true">
        <div class="modal-box max-w-3xl p-0 overflow-hidden">
            @if ($viewingStudent)
                @php
                    $log = $latestLogs[$viewingStudent->id] ?? null;
                    $current = $viewingStudent->careerStatuses->first();
                    $fromCareer = $viewingStudent->career_updated_at && $viewingStudent->career_updated_at->gt($viewingStudent->updated_at);
                @endphp

                {{-- Header --}}
                <div class="bg-neutral text-neutral-content px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-bold text-xl truncate">{{ $viewingStudent->prefix }}{{ $viewingStudent->first_name }} {{ $viewingStudent->last_name }}</h3>
                            <p class="font-mono text-sm text-neutral-content/60 mt-0.5">{{ $viewingStudent->student_code }}</p>
                        </div>
                        <button type="button" wire:click="closeDetail" class="btn btn-sm btn-circle btn-ghost text-neutral-content/70" aria-label="ปิด">✕</button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <span @class([
                            'badge badge-sm border-transparent',
                            'badge-info' => $viewingStudent->status === 'studying',
                            'badge-success' => $viewingStudent->status === 'graduated',
                            'badge-error' => $viewingStudent->status === 'dropped_out',
                        ])>
                            {{ match($viewingStudent->status) {
                                'studying' => 'กำลังศึกษา',
                                'graduated' => 'จบการศึกษา',
                                'dropped_out' => 'ออกกลางคัน',
                                default => $viewingStudent->status,
                            } }}
                        </span>
                        <span class="text-xs text-neutral-content/60">
                            ปรับปรุงล่าสุด {{ $viewingStudent->last_updated_at->format('d/m/').($viewingStudent->last_updated_at->format('Y') + 543) }}
                            {{ $viewingStudent->last_updated_at->format('H:i') }} ({{ $viewingStudent->last_updated_human }})
                            ที่{{ $fromCareer ? 'ภาวะการมีงานทำ' : 'ข้อมูลนักศึกษา' }}@if ($log) โดย {{ $log->user?->name ?? 'ระบบ' }}@endif
                        </span>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-5 max-h-[65vh] overflow-y-auto">
                    {{-- Student info --}}
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">ข้อมูลนักศึกษา</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                            <div>
                                <p class="text-xs text-base-content/50">ปีการศึกษา</p>
                                <p class="font-medium">{{ $viewingStudent->academicYear?->year ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/50">สาขาวิชา</p>
                                <p class="font-medium">{{ $viewingStudent->program ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/50">ระดับการศึกษา</p>
                                <p class="font-medium">{{ $viewingStudent->degree_level ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/50">เบอร์โทรศัพท์</p>
                                <p class="font-medium">{{ $viewingStudent->phone ?: '—' }}</p>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <p class="text-xs text-base-content/50">อีเมล</p>
                                <p class="font-medium break-all">{{ $viewingStudent->email ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-base-content/50">LINE</p>
                                <p class="font-medium">
                                    @if ($viewingStudent->line_user_id)
                                        <span class="badge badge-success badge-sm">เชื่อมต่อแล้ว</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">ยังไม่เชื่อมต่อ</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Latest career status --}}
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">
                            ภาวะการมีงานทำล่าสุด
                            @if ($viewingStudent->careerStatuses->count() > 1)
                                <span class="normal-case tracking-normal font-normal text-base-content/40">(มีประวัติทั้งหมด {{ $viewingStudent->careerStatuses->count() }} รายการ)</span>
                            @endif
                        </h4>

                        @if (! $current)
                            <p class="text-sm text-base-content/50">ยังไม่มีการบันทึกภาวะการมีงานทำสำหรับนักศึกษาคนนี้</p>
                        @else
                            <div class="rounded-box border border-base-300 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="badge" style="background-color: {{ $current->status->color() }}; color: white; border-color: transparent;">
                                            {{ $current->status->label() }}
                                        </span>
                                        <span class="text-xs text-base-content/50">ปีการศึกษาที่สำรวจ {{ $current->academicYear?->year }}</span>
                                    </div>
                                    <span class="text-xs text-base-content/50 tabular-nums">
                                        วันที่มีผล {{ $current->effective_date->format('d/m/').($current->effective_date->format('Y') + 543) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 mt-4 text-sm">
                                    @if (in_array($current->status->value, ['employed', 'entrepreneur'], true))
                                        <div>
                                            <p class="text-xs text-base-content/50">{{ $current->status->value === 'entrepreneur' ? 'ชื่อกิจการ' : 'ชื่อบริษัท' }}</p>
                                            <p class="font-medium">{{ $current->company_name ?: '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-base-content/50">ตำแหน่ง</p>
                                            <p class="font-medium">{{ $current->position ?: '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-base-content/50">เงินเดือน/รายได้</p>
                                            <p class="font-medium tabular-nums">{{ $current->monthly_salary ? number_format($current->monthly_salary, 0).' บาท' : '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-base-content/50">ตรงกับสาขาที่เรียน</p>
                                            <p class="font-medium">{{ $current->is_related_to_major === null ? '—' : ($current->is_related_to_major ? 'ตรงสาย' : 'ไม่ตรงสาย') }}</p>
                                        </div>
                                    @elseif ($current->status->value === 'further_study')
                                        <div class="col-span-2">
                                            <p class="text-xs text-base-content/50">ชื่อสถานศึกษาต่อ</p>
                                            <p class="font-medium">{{ $current->institution_name ?: '—' }}</p>
                                        </div>
                                    @endif

                                    @if ($current->workProvince)
                                        <div>
                                            <p class="text-xs text-base-content/50">ที่ตั้ง</p>
                                            <p class="font-medium">
                                                {{ collect([$current->workSubdistrict?->name_th, $current->workDistrict?->name_th, $current->workProvince?->name_th])->filter()->implode(' ') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                @if ($current->notes)
                                    <p class="text-sm text-base-content/70 mt-3 pt-3 border-t border-base-300">{{ $current->notes }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-base-300 flex justify-end gap-2">
                    <button type="button" wire:click="closeDetail" class="btn btn-ghost btn-sm">ปิด</button>
                    <a href="{{ route('students.show', $viewingStudent) }}" wire:navigate class="btn btn-primary btn-sm gap-2">
                        ดูข้อมูลเต็ม
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            @endif
        </div>
        <div class="modal-backdrop" wire:click="closeDetail"></div>
    </div>
</div>
