<div class="space-y-6">
    <div class="text-center space-y-1">
        <h1 class="text-2xl font-bold">แจ้งข้อมูลภาวะการมีงานทำ</h1>
        <p class="text-sm text-base-content/60">สำหรับนักศึกษา/ศิษย์เก่าแจ้งสถานะการทำงาน/ศึกษาต่อด้วยตนเอง</p>
    </div>

    {{-- Step: search --}}
    @if ($step === 'search')
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <label class="input input-bordered flex items-center gap-2 text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="พิมพ์ชื่อ หรือ นามสกุล..."
                        class="grow"
                        autofocus
                    >
                    <span wire:loading wire:target="search" class="loading loading-spinner loading-sm"></span>
                </label>
                <p class="text-xs text-base-content/50 mt-1">พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อเริ่มค้นหา แล้วเลือกชื่อของตนเองจากรายการ</p>
            </div>
        </div>

        @if (mb_strlen(trim($search)) < 2)
            <div class="text-center py-12 text-base-content/50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm">กรอกชื่อหรือนามสกุลด้านบนเพื่อเริ่มค้นหา</p>
            </div>
        @elseif ($candidates->isEmpty())
            <div class="text-center py-12 text-base-content/50">
                <p class="text-sm">ไม่พบชื่อที่ตรงกับ "{{ $search }}"</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($candidates as $candidateOption)
                    <button
                        type="button"
                        wire:click="selectCandidate({{ $candidateOption->id }})"
                        wire:key="candidate-{{ $candidateOption->id }}"
                        class="card bg-base-100 shadow w-full text-left hover:ring-2 hover:ring-primary transition"
                    >
                        <div class="card-body p-4">
                            <p class="font-semibold">{{ $candidateOption->prefix }}{{ $candidateOption->first_name }} {{ $candidateOption->last_name }}</p>
                            <div class="text-sm text-base-content/70 grid grid-cols-2 gap-1 mt-1">
                                <span>{{ $candidateOption->program ?: '—' }} @if($candidateOption->degree_level) ({{ $candidateOption->degree_level }}) @endif</span>
                                <span class="text-right">ปีการศึกษา {{ $candidateOption->academicYear?->year }}</span>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Step: verify identity via birth date --}}
    @if ($step === 'verify' && $candidate)
        <div class="card bg-base-100 shadow max-w-md mx-auto">
            <div class="card-body">
                <button type="button" wire:click="backToSearch" class="btn btn-ghost btn-xs gap-1 -ml-2 w-fit">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    ค้นหาใหม่
                </button>

                <h2 class="card-title text-base">ยืนยันตัวตน</h2>
                <p class="text-sm text-base-content/70">
                    คุณคือ <span class="font-semibold">{{ $candidate->prefix }}{{ $candidate->first_name }} {{ $candidate->last_name }}</span> ใช่หรือไม่?
                    กรุณากรอกวันเดือนปีเกิดเพื่อยืนยันตัวตน
                </p>

                <form wire:submit="verify" class="space-y-4 mt-2">
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">วันเดือนปีเกิด *</span></label>
                        <x-thai-date-input wire-model="birthDateInput" years-back="80" years-forward="0" />
                        @error('birthDateInput') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled" wire:target="verify">
                        <span wire:loading.remove wire:target="verify">ยืนยันตัวตน</span>
                        <span wire:loading wire:target="verify" class="loading loading-spinner loading-sm"></span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Step: the actual report form, mirrors the staff-facing career status form --}}
    @if ($step === 'form' && $verifiedStudent)
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p class="text-xs text-base-content/50">กำลังกรอกข้อมูลของ</p>
                        <p class="font-semibold">{{ $verifiedStudent->prefix }}{{ $verifiedStudent->first_name }} {{ $verifiedStudent->last_name }}</p>
                    </div>
                    <button type="button" wire:click="backToVerify" class="btn btn-ghost btn-xs">ไม่ใช่ฉัน?</button>
                </div>

                <form wire:submit="submit" class="space-y-4 mt-4">
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
                            <x-thai-date-input wire-model="effective_date" />
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
                                <input type="text" wire:model="work_location" class="input input-bordered w-full" placeholder="ชื่ออาคาร/ที่อยู่โดยละเอียด">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="label cursor-pointer justify-start gap-2">
                                    <input type="checkbox" wire:model="is_related_to_major" class="checkbox checkbox-sm">
                                    <span class="label-text">งานที่ทำตรงกับสาขาที่เรียน</span>
                                </label>
                            </div>
                        </div>
                    @endif

                    @if ($isFurtherStudy)
                        <div class="grid grid-cols-1 gap-4 p-4 rounded-box bg-base-200">
                            <div>
                                <label class="label pb-1"><span class="label-text text-xs">ชื่อสถานศึกษาต่อ *</span></label>
                                <input
                                    type="text"
                                    wire:model="institution_name"
                                    list="self-report-institution-suggestions"
                                    class="input input-bordered w-full"
                                    placeholder="เช่น มหาวิทยาลัยเทคโนโลยีราชมงคล..."
                                >
                                <datalist id="self-report-institution-suggestions">
                                    @foreach ($institutionSuggestions as $suggestion)
                                        <option value="{{ $suggestion }}"></option>
                                    @endforeach
                                </datalist>
                                @error('institution_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif

                    @if ($needsLocation)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-box bg-base-200">
                            <div class="sm:col-span-3">
                                <p class="text-xs font-semibold text-base-content/60">
                                    {{ $status === 'further_study' ? 'ที่ตั้งสถานศึกษาต่อ' : 'ที่ตั้งสถานที่ทำงาน' }}
                                </p>
                            </div>

                            <div>
                                <label class="label pb-1"><span class="label-text text-xs">จังหวัด</span></label>
                                <select wire:model.live="work_province_id" class="select select-bordered w-full">
                                    <option value="">— เลือกจังหวัด —</option>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name_th }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label pb-1"><span class="label-text text-xs">อำเภอ/เขต</span></label>
                                <select wire:model.live="work_district_id" class="select select-bordered w-full" @disabled(! $work_province_id)>
                                    <option value="">— เลือกอำเภอ/เขต —</option>
                                    @foreach ($districts as $district)
                                        <option value="{{ $district->id }}">{{ $district->name_th }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="label pb-1"><span class="label-text text-xs">ตำบล/แขวง</span></label>
                                <select wire:model="work_subdistrict_id" class="select select-bordered w-full" @disabled(! $work_district_id)>
                                    <option value="">— เลือกตำบล/แขวง —</option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}">{{ $subdistrict->name_th }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">หมายเหตุเพิ่มเติม</span></label>
                        <textarea wire:model="notes" class="textarea textarea-bordered w-full" rows="2"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit">ส่งข้อมูล</span>
                            <span wire:loading wire:target="submit" class="loading loading-spinner loading-sm"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Step: done --}}
    @if ($step === 'done')
        <div class="card bg-base-100 shadow max-w-md mx-auto">
            <div class="card-body items-center text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h2 class="text-lg font-bold">ส่งข้อมูลเรียบร้อยแล้ว</h2>
                <p class="text-sm text-base-content/70">ขอบคุณที่ให้ความร่วมมือกับทางวิทยาลัย</p>
                <a href="{{ route('public.career-status-self-report') }}" wire:navigate class="btn btn-outline btn-sm mt-2">แจ้งข้อมูลของคนอื่นอีกครั้ง</a>
            </div>
        </div>
    @endif
</div>
