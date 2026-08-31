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
                                <a href="{{ route('students.show', $student) }}" wire:navigate class="link link-hover link-primary">{{ $student->student_code }}</a>
                            </td>
                            <td>
                                <a href="{{ route('students.show', $student) }}" wire:navigate class="link link-hover link-primary">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</a>
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
                        <a href="{{ route('students.show', $student) }}" wire:navigate>
                            <p class="font-semibold link link-hover link-primary">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</p>
                            <p class="text-xs text-base-content/60 font-mono">{{ $student->student_code }}</p>
                        </a>
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
</div>
