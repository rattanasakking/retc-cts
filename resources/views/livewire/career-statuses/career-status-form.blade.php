<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">บันทึกภาวะการมีงานทำ</h1>
        <p class="text-sm text-base-content/60">บันทึกสถานะการทำงาน/ศึกษาต่อของนักศึกษาหลังสำเร็จการศึกษา</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    {{-- Step 1: student picker --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">1. เลือกนักศึกษา</h2>

            @if ($selectedStudent)
                <div class="flex items-center justify-between gap-3 p-3 rounded-box bg-base-200">
                    <div>
                        <p class="font-semibold">{{ $selectedStudent->prefix }}{{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }}</p>
                        <p class="text-xs text-base-content/60 font-mono">{{ $selectedStudent->student_code }} · {{ $selectedStudent->program ?: '—' }}</p>
                    </div>
                    <button type="button" wire:click="clearStudent" class="btn btn-ghost btn-xs">เปลี่ยน</button>
                </div>
            @else
                <label class="input input-bordered flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="studentSearch"
                        placeholder="ค้นหาชื่อหรือรหัสนักศึกษา..."
                        class="grow"
                    >
                </label>
                @error('selectedStudentId') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror

                @if ($matchingStudents->isNotEmpty())
                    <ul class="menu bg-base-200 rounded-box mt-2 p-1">
                        @foreach ($matchingStudents as $student)
                            <li wire:key="match-{{ $student->id }}">
                                <button type="button" wire:click="selectStudent({{ $student->id }})" class="flex items-center justify-between">
                                    <span>{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</span>
                                    <span class="text-xs opacity-60 font-mono">{{ $student->student_code }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif (mb_strlen(trim($studentSearch)) >= 2)
                    <p class="text-xs text-base-content/50 mt-2">ไม่พบนักศึกษาที่ตรงกับ "{{ $studentSearch }}"</p>
                @endif
            @endif
        </div>
    </div>

    {{-- Step 2: status form --}}
    <div class="card bg-base-100 shadow {{ $selectedStudent ? '' : 'opacity-50 pointer-events-none' }}">
        <div class="card-body">
            <h2 class="card-title text-base">2. รายละเอียดภาวะการมีงานทำ</h2>

            <form wire:submit="save" class="space-y-4 mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ปีการศึกษาที่สำรวจ *</span></label>
                        <select wire:model="academic_year_id" class="select select-bordered w-full">
                            <option value="">— เลือกปีการศึกษา —</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }} @if($year->is_active) (ปัจจุบัน) @endif</option>
                            @endforeach
                        </select>
                        @error('academic_year_id') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">วันที่มีผล *</span></label>
                        <input type="date" wire:model="effective_date" class="input input-bordered w-full">
                        @error('effective_date') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label pb-1"><span class="label-text text-xs">สถานะ *</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach ($statuses as $option)
                                <label @class([
                                    'btn btn-sm justify-start gap-2 font-normal',
                                    'btn-primary' => $status === $option->value,
                                    'btn-outline' => $status !== $option->value,
                                ])>
                                    <input type="radio" wire:model.live="status" value="{{ $option->value }}" class="hidden">
                                    {{ $option->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('status') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Dynamic block: only for working statuses (employed / entrepreneur) --}}
                @if ($isWorkingStatus)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-box bg-base-200">
                        <div class="sm:col-span-2">
                            <label class="label pb-1">
                                <span class="label-text text-xs">
                                    {{ $status === 'entrepreneur' ? 'ชื่อกิจการ *' : 'ชื่อบริษัท *' }}
                                </span>
                            </label>
                            <input type="text" wire:model="company_name" class="input input-bordered w-full">
                            @error('company_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">ตำแหน่ง</span></label>
                            <input type="text" wire:model="position" class="input input-bordered w-full">
                        </div>

                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">เงินเดือน/รายได้โดยประมาณ (บาท)</span></label>
                            <input type="number" step="0.01" min="0" wire:model="monthly_salary" class="input input-bordered w-full">
                            @error('monthly_salary') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">ลักษณะการจ้างงาน *</span></label>
                            <select wire:model="employment_type" class="select select-bordered w-full">
                                <option value="full_time">งานประจำ</option>
                                <option value="part_time">งานพาร์ทไทม์</option>
                                <option value="contract">งานสัญญาจ้าง</option>
                            </select>
                        </div>

                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">สถานที่ทำงาน</span></label>
                            <input type="text" wire:model="work_location" class="input input-bordered w-full">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label cursor-pointer justify-start gap-2">
                                <input type="checkbox" wire:model="is_related_to_major" class="checkbox checkbox-sm">
                                <span class="label-text">งานที่ทำตรงกับสาขาที่เรียน</span>
                            </label>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">หมายเหตุเพิ่มเติม</span></label>
                    <textarea wire:model="notes" class="textarea textarea-bordered w-full" rows="2"></textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">บันทึกข้อมูล</span>
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
