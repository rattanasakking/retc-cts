<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">นำเข้าข้อมูลนักศึกษา</h1>
            <p class="text-sm text-base-content/60">นำเข้าไฟล์ CSV พร้อมตรวจสอบความถูกต้องและข้อมูลซ้ำอัตโนมัติ</p>
        </div>
        @if ($format === 'standard')
            <a href="{{ route('students.import.template') }}" class="btn btn-outline btn-sm gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                ดาวน์โหลดเทมเพลต CSV
            </a>
        @endif
    </div>

    {{-- Upload form --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">อัปโหลดไฟล์</h2>

            <form wire:submit="import" class="space-y-4">
                <div>
                    <label class="label" for="import-format"><span class="label-text">รูปแบบไฟล์</span></label>
                    <select id="import-format" wire:model.live="format" class="select select-bordered select-sm w-full max-w-md">
                        <option value="standard">เทมเพลตมาตรฐานของระบบ</option>
                        <option value="school_report">รายงานติดตามภาวะการมีงานทำ (ไฟล์จากระบบโรงเรียนโดยตรง)</option>
                    </select>
                </div>

                <div>
                    <input
                        type="file"
                        wire:model="file"
                        accept=".csv,.txt"
                        class="file-input file-input-bordered w-full max-w-md"
                    >
                    <div wire:loading wire:target="file" class="text-xs text-base-content/60 mt-1">กำลังอัปโหลดไฟล์...</div>
                    @error('file')
                        <p class="text-sm text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="label cursor-pointer justify-start gap-2 w-fit">
                    <input type="checkbox" wire:model="updateExisting" class="checkbox checkbox-sm">
                    <span class="label-text">เติมข้อมูลที่ขาดหายไปให้นักศึกษาที่มีอยู่แล้ว (แทนที่จะข้ามแถวที่รหัสนักศึกษาซ้ำ)</span>
                </label>
                <p class="text-xs text-base-content/50 -mt-2">จะเติมเฉพาะช่องที่ยังว่างอยู่เท่านั้น ไม่แก้ไขข้อมูลที่มีอยู่แล้ว</p>

                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="import">
                    <span wire:loading.remove wire:target="import">เริ่มนำเข้าข้อมูล</span>
                    <span wire:loading wire:target="import" class="loading loading-spinner loading-sm"></span>
                    <span wire:loading wire:target="import">กำลังประมวลผล...</span>
                </button>
            </form>

            <div class="alert mt-2 text-sm bg-info/10 border border-info/20 text-base-content">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                @if ($format === 'standard')
                    <span>คอลัมน์ที่ต้องมี: <code class="text-xs">student_code, first_name, last_name, academic_year</code> — คอลัมน์เสริม: <code class="text-xs">national_id, prefix, birth_date, program, degree_level, phone, email, status</code> (birth_date รูปแบบ ค.ศ. เช่น 2007-10-02 ใช้สำหรับให้นักศึกษายืนยันตัวตนในแบบฟอร์มสาธารณะ). ระบบจะข้ามแถวที่ข้อมูลซ้ำกับที่มีอยู่แล้วโดยอัตโนมัติ พร้อมบันทึกเหตุผลไว้ในประวัติการนำเข้า</span>
                @else
                    <span>อัปโหลดไฟล์ CSV ที่ส่งออกจากระบบของโรงเรียน (รายงานติดตามภาวะการมีงานทำและศึกษาต่อ) ได้โดยตรง ไม่ต้องแก้ไขคอลัมน์เอง — ระบบจะข้ามหัวรายงาน 5 แถวแรกให้อัตโนมัติ พร้อมบันทึกสถานะการทำงาน/ศึกษาต่อของนักศึกษาแต่ละคนจากคอลัมน์ "ชื่อสถานที่ทำงาน" และ "ชื่อสถานศึกษาเรียนต่อ" ในไฟล์</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Active import progress --}}
    @if ($this->activeImport)
        @php $active = $this->activeImport; @endphp
        <div class="card bg-base-100 shadow" @if (in_array($active->status, ['pending', 'processing'])) wire:poll.2000ms @endif>
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">สถานะการนำเข้า: {{ $active->file_name }}</h2>
                    <span @class([
                        'badge',
                        'badge-ghost' => $active->status === 'pending',
                        'badge-info' => $active->status === 'processing',
                        'badge-success' => $active->status === 'completed',
                        'badge-error' => $active->status === 'failed',
                    ])>
                        {{ match($active->status) {
                            'pending' => 'รอดำเนินการ',
                            'processing' => 'กำลังนำเข้า',
                            'completed' => 'สำเร็จ',
                            'failed' => 'ล้มเหลว',
                            default => $active->status,
                        } }}
                    </span>
                </div>

                @php
                    $processed = $active->imported_rows + $active->failed_rows;
                    $percent = $active->total_rows > 0 ? min(100, round($processed / $active->total_rows * 100)) : 0;
                @endphp

                <progress class="progress progress-primary w-full" value="{{ $percent }}" max="100"></progress>
                <p class="text-xs text-base-content/60">{{ $processed }} / {{ $active->total_rows }} แถว ({{ $percent }}%)</p>

                <div class="grid grid-cols-4 gap-3 mt-2 text-center">
                    <div>
                        <p class="text-xl font-bold tabular-nums">{{ number_format($active->total_rows) }}</p>
                        <p class="text-xs text-base-content/60">ทั้งหมด</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold tabular-nums text-success">{{ number_format($active->imported_rows) }}</p>
                        <p class="text-xs text-base-content/60">สำเร็จ</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold tabular-nums text-info">{{ number_format($active->updated_rows) }}</p>
                        <p class="text-xs text-base-content/60">อัปเดตข้อมูลเดิม</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold tabular-nums text-error">{{ number_format($active->failed_rows) }}</p>
                        <p class="text-xs text-base-content/60">ล้มเหลว</p>
                    </div>
                </div>

                @if ($active->errors && count($active->errors) > 0)
                    <div class="collapse collapse-arrow bg-base-200 mt-2">
                        <input type="checkbox" />
                        <div class="collapse-title text-sm font-medium">
                            ดูรายละเอียดข้อผิดพลาด ({{ count($active->errors) }} แถว)
                        </div>
                        <div class="collapse-content">
                            <ul class="text-xs space-y-1 max-h-48 overflow-y-auto">
                                @foreach (array_slice($active->errors, 0, 50) as $error)
                                    <li>
                                        <span class="font-semibold">แถว {{ $error['row'] }}:</span>
                                        {{ implode(', ', $error['messages']) }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Import history --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">ประวัติการนำเข้าล่าสุด</h2>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ไฟล์</th>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                            <th class="text-right">ทั้งหมด</th>
                            <th class="text-right">สำเร็จ</th>
                            <th class="text-right">ล้มเหลว</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->recentImports as $log)
                            <tr>
                                <td class="max-w-[200px] truncate">{{ $log->file_name }}</td>
                                <td class="whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span @class([
                                        'badge badge-sm',
                                        'badge-ghost' => $log->status === 'pending',
                                        'badge-info' => $log->status === 'processing',
                                        'badge-success' => $log->status === 'completed',
                                        'badge-error' => $log->status === 'failed',
                                    ])>
                                        {{ match($log->status) {
                                            'pending' => 'รอดำเนินการ',
                                            'processing' => 'กำลังนำเข้า',
                                            'completed' => 'สำเร็จ',
                                            'failed' => 'ล้มเหลว',
                                            default => $log->status,
                                        } }}
                                    </span>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($log->total_rows) }}</td>
                                <td class="text-right tabular-nums text-success">{{ number_format($log->imported_rows) }}</td>
                                <td class="text-right tabular-nums text-error">{{ number_format($log->failed_rows) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-base-content/60 py-6">ยังไม่มีประวัติการนำเข้า</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
