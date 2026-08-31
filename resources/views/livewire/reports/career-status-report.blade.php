<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">รายงานภาวะการมีงานทำ</h1>
            <p class="text-sm text-base-content/60">
                สรุปภาวะการมีงานทำ{{ $graduatedOnly ? 'ของผู้สำเร็จการศึกษา' : 'ของนักศึกษาทั้งหมด' }}
                @if ($selectedYear) ปีการศึกษา {{ $selectedYear->year }} @endif
            </p>
        </div>
        <a href="{{ route('reports.export') }}" wire:navigate class="btn btn-outline btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            ส่งออกเป็นไฟล์
        </a>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4 gap-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <select wire:model.live="academicYearId" class="select select-bordered">
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }} @if ($year->is_active) (ปัจจุบัน) @endif</option>
                    @endforeach
                </select>

                <select wire:model.live="program" class="select select-bordered">
                    <option value="">ทุกแผนกวิชา</option>
                    @foreach ($programs as $programOption)
                        <option value="{{ $programOption }}">{{ $programOption }}</option>
                    @endforeach
                </select>

                <select wire:model.live="degreeLevel" class="select select-bordered">
                    <option value="">ทุกระดับ</option>
                    @foreach ($degreeLevels as $level)
                        <option value="{{ $level }}">{{ $level }}</option>
                    @endforeach
                </select>

                <select wire:model.live="careerStatus" class="select select-bordered">
                    <option value="">ทุกภาวะการมีงานทำ</option>
                    @foreach ($careerStatusTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                    <option value="none">ยังไม่ตอบแบบสำรวจ</option>
                </select>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="input input-bordered flex items-center gap-2 grow max-w-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหาชื่อ หรือรหัสนักศึกษา..." class="grow">
                </label>

                <label class="label cursor-pointer gap-2">
                    <input type="checkbox" wire:model.live="graduatedOnly" class="checkbox checkbox-sm checkbox-primary">
                    <span class="label-text text-sm">เฉพาะผู้สำเร็จการศึกษา</span>
                </label>

                @if ($program || $degreeLevel || $careerStatus || $search || ! $graduatedOnly)
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs">ล้างตัวกรอง</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary tiles --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">{{ $graduatedOnly ? 'ผู้สำเร็จการศึกษา' : 'นักศึกษาทั้งหมด' }}</p>
                <p class="text-2xl font-bold tabular-nums">{{ number_format($totals->total) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">ตอบแบบสำรวจแล้ว</p>
                <p class="text-2xl font-bold tabular-nums">{{ number_format($respondents) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
                <p class="text-xs text-base-content/50">อัตราการตอบกลับ {{ $responseRate }}%</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">มีงานทำ / ประกอบธุรกิจ</p>
                <p class="text-2xl font-bold tabular-nums text-primary">{{ number_format($totals->employed) }} <span class="text-sm font-normal text-base-content/60">คน</span></p>
                <p class="text-xs text-base-content/50">{{ $employedRate }}% ของผู้ตอบแบบสำรวจ</p>
            </div>
        </div>
        <div class="card bg-base-100 shadow">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">เงินเดือนเฉลี่ย</p>
                <p class="text-2xl font-bold tabular-nums">{{ $avgSalary ? number_format($avgSalary, 0) : '—' }} <span class="text-sm font-normal text-base-content/60">บาท</span></p>
            </div>
        </div>
    </div>

    {{-- Summary by program --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-0">
            <h2 class="card-title text-base px-4 pt-4">สรุปแยกตามแผนกวิชา</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>แผนกวิชา</th>
                            <th class="text-right">ทั้งหมด</th>
                            <th class="text-right">มีงานทำ</th>
                            <th class="text-right">ศึกษาต่อ</th>
                            <th class="text-right">ว่างงาน</th>
                            <th class="text-right">อื่นๆ</th>
                            <th class="text-right">ยังไม่ตอบ</th>
                            <th class="text-right">% มีงานทำ</th>
                        </tr>
                    </thead>
                    <tbody class="tabular-nums">
                        @forelse ($summary as $row)
                            @php $rowRespondents = $row->total - $row->no_response; @endphp
                            <tr wire:key="summary-{{ $loop->index }}">
                                <td class="font-medium">{{ $row->program }}</td>
                                <td class="text-right">{{ number_format($row->total) }}</td>
                                <td class="text-right text-primary font-semibold">{{ number_format($row->employed) }}</td>
                                <td class="text-right">{{ number_format($row->further_study) }}</td>
                                <td class="text-right">{{ number_format($row->unemployed) }}</td>
                                <td class="text-right">{{ number_format($row->other) }}</td>
                                <td class="text-right text-base-content/50">{{ number_format($row->no_response) }}</td>
                                <td class="text-right">{{ $rowRespondents > 0 ? round($row->employed / $rowRespondents * 100).'%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-base-content/60 py-8">ไม่มีข้อมูลตามเงื่อนไขที่เลือก</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($summary->isNotEmpty())
                        <tfoot>
                            <tr class="font-semibold text-base-content tabular-nums">
                                <td>รวมทุกแผนก</td>
                                <td class="text-right">{{ number_format($totals->total) }}</td>
                                <td class="text-right">{{ number_format($totals->employed) }}</td>
                                <td class="text-right">{{ number_format($totals->further_study) }}</td>
                                <td class="text-right">{{ number_format($totals->unemployed) }}</td>
                                <td class="text-right">{{ number_format($totals->other) }}</td>
                                <td class="text-right">{{ number_format($totals->no_response) }}</td>
                                <td class="text-right">{{ $respondents > 0 ? $employedRate.'%' : '—' }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    {{-- Student-by-student listing --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-0">
            <h2 class="card-title text-base px-4 pt-4">รายชื่อนักศึกษา</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-สกุล</th>
                            <th>แผนกวิชา</th>
                            <th>ระดับ</th>
                            <th>ภาวะการมีงานทำ</th>
                            <th>สถานประกอบการ / สถานศึกษา</th>
                            <th>ตำแหน่ง</th>
                            <th class="text-right">เงินเดือน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php $career = $student->careerStatuses->first(); @endphp
                            <tr wire:key="report-student-{{ $student->id }}">
                                <td class="font-mono text-sm">
                                    <a href="{{ route('students.show', $student) }}" wire:navigate class="link link-hover link-primary">{{ $student->student_code }}</a>
                                </td>
                                <td class="whitespace-nowrap">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</td>
                                <td>{{ $student->program ?: '—' }}</td>
                                <td>{{ $student->degree_level ?: '—' }}</td>
                                <td>
                                    @if ($career)
                                        <span class="badge badge-sm whitespace-nowrap" style="background-color: {{ $career->status->color() }}; color: white; border-color: transparent;">
                                            {{ $career->status->label() }}
                                        </span>
                                    @else
                                        <span class="badge badge-sm badge-ghost whitespace-nowrap">ยังไม่ตอบแบบสำรวจ</span>
                                    @endif
                                </td>
                                <td>{{ $career?->company_name ?: ($career?->institution_name ?: '—') }}</td>
                                <td>{{ $career?->position ?: '—' }}</td>
                                <td class="text-right tabular-nums">{{ $career?->monthly_salary ? number_format($career->monthly_salary, 0) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-base-content/60 py-8">ไม่พบนักศึกษาตามเงื่อนไขที่เลือก</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $students->links() }}</div>
        </div>
    </div>
</div>
