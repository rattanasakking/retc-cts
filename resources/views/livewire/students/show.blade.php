<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <a href="{{ route('students.index') }}" wire:navigate class="btn btn-ghost btn-xs gap-1 -ml-2 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                กลับไปหน้ารายชื่อนักศึกษา
            </a>
            <h1 class="text-2xl font-bold">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</h1>
            <p class="text-sm text-base-content/60 font-mono">{{ $student->student_code }}</p>
        </div>
        <span @class([
            'badge badge-lg',
            'badge-info' => $student->status === 'studying',
            'badge-success' => $student->status === 'graduated',
            'badge-error' => $student->status === 'dropped_out',
        ])>
            {{ match($student->status) {
                'studying' => 'กำลังศึกษา',
                'graduated' => 'จบการศึกษา',
                'dropped_out' => 'ออกกลางคัน',
                default => $student->status,
            } }}
        </span>
    </div>

    {{-- Student info --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">ข้อมูลนักศึกษา</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 mt-2 text-sm">
                <div>
                    <p class="text-xs text-base-content/50">เลขบัตรประชาชน</p>
                    <p class="font-medium font-mono">{{ $student->national_id ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">ปีการศึกษา</p>
                    <p class="font-medium">{{ $student->academicYear?->year ? 'ปีการศึกษา '.$student->academicYear->year : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">สาขาวิชา</p>
                    <p class="font-medium">{{ $student->program ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">ระดับการศึกษา</p>
                    <p class="font-medium">{{ $student->degree_level ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">เบอร์โทรศัพท์</p>
                    <p class="font-medium">{{ $student->phone ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">อีเมล</p>
                    <p class="font-medium">{{ $student->email ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">LINE</p>
                    <p class="font-medium">
                        @if ($student->line_user_id)
                            <span class="badge badge-success badge-sm">เชื่อมต่อแล้ว</span>
                        @else
                            <span class="badge badge-ghost badge-sm">ยังไม่เชื่อมต่อ</span>
                        @endif
                    </p>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-xs text-base-content/50">ที่อยู่</p>
                    <p class="font-medium">{{ $student->address ?: '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Career status history --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">ประวัติภาวะการมีงานทำ</h2>

            @if ($careerStatuses->isEmpty())
                <p class="text-sm text-base-content/50 mt-2">ยังไม่มีการบันทึกภาวะการมีงานทำสำหรับนักศึกษาคนนี้</p>
            @else
                <div class="mt-2 space-y-3">
                    @foreach ($careerStatuses as $careerStatus)
                        <div class="p-4 rounded-box border border-base-300 {{ $careerStatus->is_current ? 'bg-base-100' : 'bg-base-200/50' }}" wire:key="career-status-{{ $careerStatus->id }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge" style="background-color: {{ $careerStatus->status->color() }}; color: white; border-color: transparent;">
                                        {{ $careerStatus->status->label() }}
                                    </span>
                                    @if ($careerStatus->is_current)
                                        <span class="badge badge-outline badge-sm">ล่าสุด</span>
                                    @endif
                                    <span class="text-xs text-base-content/50">
                                        ปีการศึกษาที่สำรวจ {{ $careerStatus->academicYear?->year }}
                                    </span>
                                </div>
                                <span class="text-xs text-base-content/50 tabular-nums">
                                    วันที่มีผล {{ $careerStatus->effective_date->format('d/m/').($careerStatus->effective_date->format('Y') + 543) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-2 mt-3 text-sm">
                                @if (in_array($careerStatus->status->value, ['employed', 'entrepreneur'], true))
                                    <div>
                                        <p class="text-xs text-base-content/50">{{ $careerStatus->status->value === 'entrepreneur' ? 'ชื่อกิจการ' : 'ชื่อบริษัท' }}</p>
                                        <p class="font-medium">{{ $careerStatus->company_name ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-base-content/50">ตำแหน่ง</p>
                                        <p class="font-medium">{{ $careerStatus->position ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-base-content/50">เงินเดือน/รายได้</p>
                                        <p class="font-medium tabular-nums">{{ $careerStatus->monthly_salary ? number_format($careerStatus->monthly_salary, 0).' บาท' : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-base-content/50">ลักษณะการจ้างงาน</p>
                                        <p class="font-medium">
                                            {{ match($careerStatus->employment_type) {
                                                'full_time' => 'งานประจำ',
                                                'part_time' => 'งานพาร์ทไทม์',
                                                'contract' => 'งานสัญญาจ้าง',
                                                default => $careerStatus->employment_type ?: '—',
                                            } }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-base-content/50">ตรงกับสาขาที่เรียน</p>
                                        <p class="font-medium">{{ $careerStatus->is_related_to_major === null ? '—' : ($careerStatus->is_related_to_major ? 'ตรงสาย' : 'ไม่ตรงสาย') }}</p>
                                    </div>
                                @elseif ($careerStatus->status->value === 'further_study')
                                    <div>
                                        <p class="text-xs text-base-content/50">ชื่อสถานศึกษาต่อ</p>
                                        <p class="font-medium">{{ $careerStatus->institution_name ?: '—' }}</p>
                                    </div>
                                @endif

                                @if ($careerStatus->workProvince)
                                    <div>
                                        <p class="text-xs text-base-content/50">ที่ตั้ง</p>
                                        <p class="font-medium">
                                            {{ collect([$careerStatus->workSubdistrict?->name_th, $careerStatus->workDistrict?->name_th, $careerStatus->workProvince?->name_th])->filter()->implode(' ') }}
                                        </p>
                                    </div>
                                @endif

                                @if ($careerStatus->work_location)
                                    <div>
                                        <p class="text-xs text-base-content/50">ที่อยู่โดยละเอียด</p>
                                        <p class="font-medium">{{ $careerStatus->work_location }}</p>
                                    </div>
                                @endif
                            </div>

                            @if ($careerStatus->notes)
                                <p class="text-sm text-base-content/70 mt-3 pt-3 border-t border-base-300">{{ $careerStatus->notes }}</p>
                            @endif

                            <div class="flex items-center gap-2 mt-3 pt-3 border-t border-base-300 text-xs text-base-content/40">
                                <span>
                                    ที่มา:
                                    {{ match($careerStatus->source) {
                                        'manual' => 'บันทึกโดยเจ้าหน้าที่',
                                        'imported' => 'นำเข้าจากไฟล์ CSV',
                                        'survey' => 'แบบสำรวจ',
                                        default => $careerStatus->source,
                                    } }}
                                </span>
                                @if ($careerStatus->verifiedBy)
                                    <span>· บันทึกโดย {{ $careerStatus->verifiedBy->name }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
