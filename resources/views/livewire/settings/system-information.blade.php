<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold">ข้อมูลระบบ</h1>
        <p class="text-sm text-base-content/60">ชื่อ โลโก้ สี และข้อมูลติดต่อที่แสดงทั่วทั้งเว็บไซต์ — ตั้งค่าที่นี่ที่เดียว ไม่ต้องแก้โค้ด</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- ชื่อสถาบัน --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <h2 class="card-title text-base">ชื่อที่แสดง</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ชื่อระบบ *</span></label>
                        <input type="text" wire:model.blur="system_name" class="input input-bordered w-full" placeholder="เช่น ระบบติดตามภาวะการมีงานทำ">
                        <p class="text-xs text-base-content/50 mt-1">ใช้เป็นชื่อหน้าเว็บ (แท็บเบราว์เซอร์) และหน้าเข้าสู่ระบบ</p>
                        @error('system_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ชื่อย่อ</span></label>
                        <input type="text" wire:model.blur="short_name" class="input input-bordered w-full" placeholder="เช่น RETC-CTS">
                        <p class="text-xs text-base-content/50 mt-1">ใช้ในแถบเมนูด้านข้าง บนมือถือ และตอนติดตั้งเป็นแอป ถ้าเว้นว่างจะใช้ชื่อระบบ</p>
                        @error('short_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label pb-1"><span class="label-text text-xs">ชื่อวิทยาลัย</span></label>
                        <input type="text" wire:model.blur="college_name" class="input input-bordered w-full" placeholder="เช่น วิทยาลัยเทคนิค...">
                        <p class="text-xs text-base-content/50 mt-1">แสดงใต้ชื่อระบบ และบนหัวกระดาษรายงาน PDF</p>
                        @error('college_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- โลโก้และสี --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <h2 class="card-title text-base">โลโก้และสีประจำสถาบัน</h2>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">โลโก้</span></label>

                    <div class="flex items-center gap-4 flex-wrap">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 rounded-lg object-cover border border-base-300" alt="ตัวอย่างโลโก้ใหม่">
                        @elseif ($currentLogoPath)
                            <img src="{{ $this->currentLogoUrl }}" class="h-16 w-16 rounded-lg object-cover border border-base-300" alt="โลโก้ปัจจุบัน">
                        @else
                            <div class="h-16 w-16 rounded-lg bg-base-200 flex items-center justify-center text-base-content/40 text-xs">ไม่มีโลโก้</div>
                        @endif

                        <div class="flex-1 min-w-[12rem]">
                            <input type="file" wire:model="logo" accept="image/*" class="file-input file-input-bordered file-input-sm w-full">
                            <div wire:loading wire:target="logo" class="text-xs text-base-content/60 mt-1">กำลังอัปโหลด...</div>
                            @error('logo') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($currentLogoPath && ! $logo)
                            <button type="button" wire:click="removeLogo" wire:confirm="ต้องการลบโลโก้ปัจจุบันหรือไม่?" class="btn btn-ghost btn-xs text-error">ลบโลโก้</button>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/50 mt-1">ไฟล์รูปภาพ ไม่เกิน 2MB — แนะนำรูปสี่เหลี่ยมจัตุรัส จะแทนที่ไอคอนเริ่มต้นทุกหน้า</p>
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">สีหลัก</span></label>
                    <div class="flex items-center gap-3 flex-wrap">
                        <input type="color" wire:model.live="primary_color" class="h-10 w-14 rounded border border-base-300 bg-base-100 cursor-pointer">
                        <input type="text" wire:model.blur="primary_color" class="input input-bordered input-sm w-32 font-mono" placeholder="#2563a8">

                        <div class="flex gap-1 flex-wrap">
                            @foreach ($colorPresets as $hex => $label)
                                <button
                                    type="button"
                                    wire:click="useColor('{{ $hex }}')"
                                    title="{{ $label }}"
                                    class="h-7 w-7 rounded-full border-2 {{ $primary_color === $hex ? 'border-base-content' : 'border-base-300' }}"
                                    style="background-color: {{ $hex }}"
                                    aria-label="{{ $label }}"
                                ></button>
                            @endforeach
                        </div>
                    </div>
                    @error('primary_color') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-base-content/50 mt-1">ใช้กับปุ่ม เมนูที่เลือกอยู่ และแถบสีต่างๆ เว้นว่างไว้เพื่อใช้สีเริ่มต้นของระบบ</p>

                    @if (preg_match('/^#[0-9a-fA-F]{6}$/', $primary_color))
                        @php $previewInk = (new \App\Models\SystemSetting(['primary_color' => $primary_color]))->brandContentColor(); @endphp
                        <div class="mt-3 p-3 rounded-box border border-base-300 flex items-center gap-3 flex-wrap">
                            <span class="text-xs text-base-content/50">ตัวอย่าง</span>
                            <span class="btn btn-sm border-transparent pointer-events-none" style="background-color: {{ $primary_color }}; color: {{ $previewInk }};">
                                ปุ่มหลัก
                            </span>
                            <span class="badge border-transparent" style="background-color: {{ $primary_color }}; color: {{ $previewInk }};">
                                ป้ายสถานะ
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ข้อมูลติดต่อ --}}
        <div class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <h2 class="card-title text-base">ข้อมูลติดต่อ</h2>
                <p class="text-sm text-base-content/60 -mt-2">แสดงท้ายหน้าสาธารณะ เพื่อให้นักศึกษาที่ติดปัญหาตอนแจ้งข้อมูลรู้ว่าจะถามใคร</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">อีเมลติดต่อ</span></label>
                        <input type="email" wire:model.blur="contact_email" class="input input-bordered w-full" placeholder="เช่น guidance@college.ac.th">
                        @error('contact_email') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">เบอร์โทรติดต่อ</span></label>
                        <input type="text" wire:model.blur="contact_phone" class="input input-bordered w-full" placeholder="เช่น 043-511-111">
                        @error('contact_phone') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">บันทึก</span>
                <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
            </button>
        </div>
    </form>
</div>
