<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">ส่งออกรายงานภาวะการมีงานทำ</h1>
        <p class="text-sm text-base-content/60">เลือกเงื่อนไขแล้วส่งออกเป็นไฟล์ Excel หรือ PDF</p>
    </div>

    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ปีการศึกษา *</span></label>
                    <select wire:model.live="academicYearId" class="select select-bordered w-full">
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }} @if($year->is_active) (ปัจจุบัน) @endif</option>
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
                    <label class="label pb-1"><span class="label-text text-xs">ระดับ</span></label>
                    <select wire:model.live="degreeLevel" class="select select-bordered w-full">
                        <option value="">ทุกระดับ</option>
                        @foreach ($degreeLevels as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="alert bg-info/10 border border-info/20 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <span>พบข้อมูลนักศึกษา <strong class="tabular-nums">{{ number_format($previewCount) }}</strong> คน ตามเงื่อนไขที่เลือก</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button" wire:click="exportExcel" class="btn btn-success flex-1 gap-2" wire:loading.attr="disabled" wire:target="exportExcel,exportPdf">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    <span wire:loading.remove wire:target="exportExcel">ส่งออก Excel</span>
                    <span wire:loading wire:target="exportExcel" class="loading loading-spinner loading-sm"></span>
                </button>

                <button type="button" wire:click="exportPdf" class="btn btn-error flex-1 gap-2" wire:loading.attr="disabled" wire:target="exportExcel,exportPdf">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span wire:loading.remove wire:target="exportPdf">ส่งออก PDF</span>
                    <span wire:loading wire:target="exportPdf" class="loading loading-spinner loading-sm"></span>
                </button>
            </div>
        </div>
    </div>
</div>
