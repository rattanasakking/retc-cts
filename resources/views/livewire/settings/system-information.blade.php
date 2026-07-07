<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">ข้อมูลระบบ</h1>
        <p class="text-sm text-base-content/60">ชื่อระบบ ชื่อวิทยาลัย และโลโก้ที่แสดงทั่วทั้งเว็บไซต์</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ชื่อระบบ *</span></label>
                    <input type="text" wire:model="system_name" class="input input-bordered w-full">
                    @error('system_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ชื่อวิทยาลัย</span></label>
                    <input type="text" wire:model="college_name" class="input input-bordered w-full" placeholder="เช่น วิทยาลัยเทคนิค...">
                    @error('college_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">โลโก้ระบบ</span></label>

                    <div class="flex items-center gap-4">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-cover border border-base-300" alt="ตัวอย่างโลโก้ใหม่">
                        @elseif ($currentLogoPath)
                            <img src="{{ $this->currentLogoUrl }}" class="h-16 w-16 rounded-lg object-cover border border-base-300" alt="โลโก้ปัจจุบัน">
                        @else
                            <div class="h-16 w-16 rounded-lg bg-base-200 flex items-center justify-center text-base-content/40 text-xs">ไม่มีโลโก้</div>
                        @endif

                        <div class="flex-1">
                            <input type="file" wire:model="logo" accept="image/*" class="file-input file-input-bordered file-input-sm w-full">
                            <div wire:loading wire:target="logo" class="text-xs text-base-content/60 mt-1">กำลังอัปโหลด...</div>
                            @error('logo') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($currentLogoPath && ! $logo)
                            <button type="button" wire:click="removeLogo" wire:confirm="ต้องการลบโลโก้ปัจจุบันหรือไม่?" class="btn btn-ghost btn-xs text-error">ลบโลโก้</button>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/50 mt-1">รองรับไฟล์รูปภาพ ขนาดไม่เกิน 2MB</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">บันทึก</span>
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
