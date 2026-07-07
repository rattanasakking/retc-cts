<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">แจ้งเตือนนักศึกษาที่ยังไม่กรอกข้อมูล</h1>
        <p class="text-sm text-base-content/60">ส่งอีเมล/LINE เตือนนักศึกษาที่ยังไม่ตอบแบบสำรวจภาวะการมีงานทำ</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ปีการศึกษา *</span></label>
                    <select wire:model.live="academicYearId" class="select select-bordered w-full">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }} @if ($year->is_active) (ปัจจุบัน) @endif</option>
                        @endforeach
                    </select>
                    @error('academicYearId') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">แผนกวิชา</span></label>
                    <select wire:model.live="program" class="select select-bordered w-full">
                        <option value="">ทุกแผนก</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program }}">{{ $program }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ระดับการศึกษา</span></label>
                    <select wire:model.live="degreeLevel" class="select select-bordered w-full">
                        <option value="">ทุกระดับ</option>
                        @foreach ($degreeLevels as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="alert bg-warning/10 border border-warning/20 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <span>พบนักศึกษาที่<strong>ยังไม่ตอบแบบสำรวจ</strong> <strong class="tabular-nums">{{ number_format($nonResponderCount) }}</strong> คน ตามเงื่อนไขที่เลือก</span>
            </div>

            <div class="flex justify-end">
                <button
                    type="button"
                    wire:click="sendReminders"
                    wire:confirm="ยืนยันส่งการแจ้งเตือนไปยังนักศึกษา {{ $nonResponderCount }} คนหรือไม่?"
                    class="btn btn-primary gap-2"
                    wire:loading.attr="disabled"
                    wire:target="sendReminders"
                    {{ $nonResponderCount === 0 ? 'disabled' : '' }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    <span wire:loading.remove wire:target="sendReminders">ส่งการแจ้งเตือน</span>
                    <span wire:loading wire:target="sendReminders">กำลังส่ง...</span>
                </button>
            </div>
        </div>
    </div>
</div>
