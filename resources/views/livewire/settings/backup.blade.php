<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">สำรอง/กู้คืนข้อมูล</h1>
        <p class="text-sm text-base-content/60">สำรองฐานข้อมูลทั้งหมดเป็นไฟล์ .sql และกู้คืนได้เมื่อจำเป็น</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    @if ($actionError && ! $restoreFilename && ! $restoreUpload)
        <div class="alert alert-error text-sm">{{ $actionError }}</div>
    @endif

    {{-- Create backup --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">สร้างข้อมูลสำรองใหม่</h2>
            <p class="text-sm text-base-content/60">ส่งออกฐานข้อมูลทั้งหมด ณ ขณะนี้เป็นไฟล์ .sql</p>
            <div>
                <button type="button" wire:click="createBackup" class="btn btn-primary gap-2" wire:loading.attr="disabled" wire:target="createBackup">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span wire:loading.remove wire:target="createBackup">สร้างข้อมูลสำรอง</span>
                    <span wire:loading wire:target="createBackup">กำลังสำรองข้อมูล...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Existing backups --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">ไฟล์สำรองข้อมูล</h2>

            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ไฟล์</th>
                            <th>ขนาด</th>
                            <th>วันที่สร้าง</th>
                            <th class="text-right">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($files as $file)
                            <tr wire:key="backup-{{ $file['name'] }}">
                                <td class="font-mono text-xs">{{ $file['name'] }}</td>
                                <td class="whitespace-nowrap">{{ number_format($file['size'] / 1024, 1) }} KB</td>
                                <td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::createFromTimestamp($file['modified'])->format('d/m/Y H:i') }}</td>
                                <td class="text-right space-x-1 whitespace-nowrap">
                                    <button type="button" wire:click="download('{{ $file['name'] }}')" class="btn btn-ghost btn-xs">ดาวน์โหลด</button>
                                    <button type="button" wire:click="confirmRestore('{{ $file['name'] }}')" class="btn btn-ghost btn-xs text-warning">กู้คืน</button>
                                    <button type="button" wire:click="deleteBackup('{{ $file['name'] }}')" wire:confirm="ลบไฟล์สำรองข้อมูลนี้หรือไม่?" class="btn btn-ghost btn-xs text-error">ลบ</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-base-content/60 py-8">ยังไม่มีไฟล์สำรองข้อมูล</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Restore from uploaded file --}}
    <div class="card bg-base-100 shadow border border-warning/30">
        <div class="card-body">
            <h2 class="card-title text-base text-warning">กู้คืนจากไฟล์ภายนอก</h2>
            <p class="text-sm text-base-content/60">อัปโหลดไฟล์ .sql เพื่อกู้คืนข้อมูล (ใช้แทนที่ข้อมูลปัจจุบันทั้งหมด)</p>
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <input type="file" wire:model="restoreUpload" accept=".sql" class="file-input file-input-bordered file-input-sm w-full sm:w-auto">
                <button type="button" wire:click="confirmRestoreFromUpload" class="btn btn-outline btn-warning btn-sm">กู้คืนจากไฟล์นี้</button>
            </div>
            @error('restoreUpload') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Restore confirmation modal (danger zone) --}}
    <div class="modal {{ ($restoreFilename || $restoreUpload) ? 'modal-open' : '' }}">
        <div class="modal-box border-2 border-error">
            <h3 class="font-bold text-lg text-error">⚠ ยืนยันการกู้คืนข้อมูล</h3>
            <p class="py-2 text-sm">
                การกู้คืนจะ<strong>เขียนทับข้อมูลปัจจุบันทั้งหมด</strong>ด้วยข้อมูลจาก
                @if ($restoreFilename)
                    <span class="font-mono">{{ $restoreFilename }}</span>
                @else
                    ไฟล์ที่อัปโหลด
                @endif
                — ระบบจะสร้างข้อมูลสำรองของสถานะปัจจุบันไว้ให้อัตโนมัติก่อนกู้คืน แต่การกระทำนี้ยังคงมีความเสี่ยงสูง
            </p>

            @if ($actionError)
                <div class="alert alert-error text-sm my-2">{{ $actionError }}</div>
            @endif

            <label class="label pb-1"><span class="label-text text-xs">พิมพ์ <strong>RESTORE</strong> เพื่อยืนยัน</span></label>
            <input type="text" wire:model="confirmationText" class="input input-bordered input-error w-full" placeholder="RESTORE">

            <div class="modal-action">
                <button type="button" wire:click="cancelRestore" class="btn btn-ghost">ยกเลิก</button>
                <button
                    type="button"
                    wire:click="performRestore"
                    class="btn btn-error"
                    wire:loading.attr="disabled"
                    wire:target="performRestore"
                    {{ $confirmationText !== 'RESTORE' ? 'disabled' : '' }}
                >
                    <span wire:loading.remove wire:target="performRestore">ยืนยันกู้คืนข้อมูล</span>
                    <span wire:loading wire:target="performRestore">กำลังกู้คืน...</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="cancelRestore"></div>
    </div>
</div>
