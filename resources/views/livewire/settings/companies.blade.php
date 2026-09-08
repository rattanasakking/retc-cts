<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">ฐานข้อมูลบริษัท / ห้างหุ้นส่วน</h1>
        <p class="text-sm text-base-content/60">
            รายชื่อนิติบุคคลที่ใช้ช่วยเติมชื่อสถานประกอบการอัตโนมัติ ทั้งในหน้าแจ้งข้อมูลของนักศึกษาและฟอร์มของเจ้าหน้าที่
        </p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    {{-- Current state --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="card bg-base-100">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">รายชื่อทั้งหมด</p>
                <p class="kpi">{{ number_format($total) }}</p>
            </div>
        </div>
        <div class="card bg-base-100">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">จากชุดข้อมูล DBD</p>
                <p class="kpi text-primary">{{ number_format($fromDbd) }}</p>
            </div>
        </div>
        <div class="card bg-base-100">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50">อัปเดตล่าสุด</p>
                <p class="text-lg font-semibold">
                    {{ $lastUpdated ? \App\Support\ThaiDate::short(\Illuminate\Support\Carbon::parse($lastUpdated)) : '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Update --}}
    <div class="card bg-base-100">
        <div class="card-body space-y-5">
            <div>
                <h2 class="card-title text-base">อัปเดตข้อมูล</h2>
                <p class="text-sm text-base-content/60">
                    ดาวน์โหลดชุด "ข้อมูลทะเบียนนิติบุคคล" แบบ CSV จาก
                    <a href="https://opendata.dbd.go.th/" target="_blank" rel="noopener" class="link link-primary">opendata.dbd.go.th</a>
                    แล้วอัปโหลดที่นี่ หรือวางลิงก์ไฟล์ให้ระบบดึงเอง — นำเข้าซ้ำได้ ระบบจะอัปเดตรายการเดิมไม่สร้างซ้ำ
                </p>
            </div>

            <div>
                <label class="label pb-1"><span class="label-text text-xs">เก็บเฉพาะจังหวัด (แนะนำ)</span></label>
                <input type="text" wire:model="onlyProvince" class="input input-bordered w-full sm:max-w-xs" placeholder="เช่น ร้อยเอ็ด — เว้นว่างเพื่อเก็บทั้งไฟล์">
                <p class="text-xs text-base-content/50 mt-1">
                    ทั้งประเทศมีนิติบุคคลหลายแสนราย เกินความจำเป็นและทำให้ค้นช้าลง กรอกชื่อจังหวัดให้ตรงกับที่เขียนในไฟล์
                </p>
                @error('onlyProvince') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {{-- Upload --}}
                <div class="rounded-box border border-base-300 p-4 space-y-3">
                    <p class="font-semibold text-sm">อัปโหลดไฟล์ CSV</p>
                    <input type="file" wire:model="file" accept=".csv,text/csv" class="file-input file-input-bordered file-input-sm w-full">
                    <div wire:loading wire:target="file" class="text-xs text-base-content/60">กำลังอัปโหลด...</div>
                    @error('file') <p class="text-xs text-error">{{ $message }}</p> @enderror

                    <button type="button" wire:click="importFile" class="btn btn-primary btn-sm w-full gap-2"
                            wire:loading.attr="disabled" wire:target="importFile,file" @disabled(! $file)>
                        <span wire:loading.remove wire:target="importFile">นำเข้าจากไฟล์</span>
                        <span wire:loading wire:target="importFile" class="loading loading-spinner loading-sm"></span>
                    </button>
                    <p class="text-xs text-base-content/50">ไฟล์ไม่เกิน 50MB</p>
                </div>

                {{-- From URL --}}
                <div class="rounded-box border border-base-300 p-4 space-y-3">
                    <p class="font-semibold text-sm">ดึงจากลิงก์</p>
                    <input type="url" wire:model="url" class="input input-bordered input-sm w-full" placeholder="https://opendata.dbd.go.th/.../file.csv">
                    @error('url') <p class="text-xs text-error">{{ $message }}</p> @enderror

                    <button type="button" wire:click="importUrl" class="btn btn-outline btn-primary btn-sm w-full gap-2"
                            wire:loading.attr="disabled" wire:target="importUrl">
                        <span wire:loading.remove wire:target="importUrl">ดาวน์โหลดและนำเข้า</span>
                        <span wire:loading wire:target="importUrl" class="loading loading-spinner loading-sm"></span>
                    </button>
                    <p class="text-xs text-base-content/50">เซิร์ฟเวอร์ต้องต่ออินเทอร์เน็ตออกได้ และไฟล์ใหญ่อาจใช้เวลาสักครู่</p>
                </div>
            </div>

            <div class="pt-4 border-t border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-sm">เก็บชื่อที่เคยกรอกไว้แล้ว</p>
                    <p class="text-xs text-base-content/50">ดึงชื่อสถานประกอบการจากภาวะการมีงานทำที่บันทึกไว้แล้วเข้ามาเป็นตัวช่วยเติมด้วย</p>
                </div>
                <button type="button" wire:click="importFromExisting" class="btn btn-outline btn-sm gap-2"
                        wire:loading.attr="disabled" wire:target="importFromExisting">
                    <span wire:loading.remove wire:target="importFromExisting">รวมชื่อจากข้อมูลเดิม</span>
                    <span wire:loading wire:target="importFromExisting" class="loading loading-spinner loading-sm"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Browse --}}
    <div class="card bg-base-100">
        <div class="card-body p-0">
            <div class="p-4 pb-0 flex flex-wrap items-center justify-between gap-3">
                <h2 class="card-title text-base">รายชื่อในระบบ</h2>
                <label class="input input-bordered input-sm flex items-center gap-2 w-full sm:w-72">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="ค้นหาชื่อบริษัท..." class="grow">
                </label>
            </div>

            <div class="overflow-x-auto mt-2">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ชื่อนิติบุคคล</th>
                            <th>ประเภท</th>
                            <th>จังหวัด</th>
                            <th>ที่มา</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr wire:key="company-{{ $company->id }}">
                                <td>
                                    {{ $company->name }}
                                    @if ($company->juristic_id)
                                        <span class="text-xs text-base-content/40 font-mono ml-1">{{ $company->juristic_id }}</span>
                                    @endif
                                </td>
                                <td class="text-sm">{{ $company->type ?: '—' }}</td>
                                <td class="text-sm">{{ $company->province ?: '—' }}</td>
                                <td>
                                    <span @class(['badge badge-sm', 'badge-primary' => $company->source === 'dbd', 'badge-ghost' => $company->source !== 'dbd'])>
                                        {{ $company->source === 'dbd' ? 'DBD' : 'กรอกเอง' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-base-content/60 py-8">
                                    ยังไม่มีรายชื่อในระบบ — อัปโหลดไฟล์จาก DBD หรือกด "รวมชื่อจากข้อมูลเดิม" ด้านบน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">{{ $companies->links() }}</div>
        </div>
    </div>
</div>
