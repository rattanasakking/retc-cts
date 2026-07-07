<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">ปีการศึกษา</h1>
            <p class="text-sm text-base-content/60">จัดการปีการศึกษาที่ใช้อ้างอิงทั้งระบบ (นักศึกษา, ภาวะการมีงานทำ, รายงาน)</p>
        </div>
        <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            เพิ่มปีการศึกษา
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    {{-- Desktop table --}}
    <div class="card bg-base-100 shadow hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>ปีการศึกษา</th>
                        <th>วันที่เริ่มต้น</th>
                        <th>วันที่สิ้นสุด</th>
                        <th>สถานะ</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($years as $y)
                        <tr wire:key="year-row-{{ $y->id }}">
                            <td class="font-semibold">{{ $y->year }}</td>
                            <td>{{ $y->start_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $y->end_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if ($y->is_active)
                                    <span class="badge badge-success badge-sm">ปัจจุบัน</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">ไม่ใช้งาน</span>
                                @endif
                            </td>
                            <td class="text-right space-x-1 whitespace-nowrap">
                                <button type="button" wire:click="openEditModal({{ $y->id }})" class="btn btn-ghost btn-xs">แก้ไข</button>
                                <button type="button" wire:click="confirmDelete({{ $y->id }})" class="btn btn-ghost btn-xs text-error">ลบ</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-base-content/60 py-8">ยังไม่มีปีการศึกษาในระบบ</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($years as $y)
            <div class="card bg-base-100 shadow" wire:key="year-card-{{ $y->id }}">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-lg">ปีการศึกษา {{ $y->year }}</p>
                        @if ($y->is_active)
                            <span class="badge badge-success badge-sm">ปัจจุบัน</span>
                        @else
                            <span class="badge badge-ghost badge-sm">ไม่ใช้งาน</span>
                        @endif
                    </div>
                    <p class="text-sm text-base-content/60">
                        {{ $y->start_date?->format('d/m/Y') ?? '—' }} – {{ $y->end_date?->format('d/m/Y') ?? '—' }}
                    </p>
                    <div class="flex gap-2 mt-2">
                        <button type="button" wire:click="openEditModal({{ $y->id }})" class="btn btn-outline btn-xs flex-1">แก้ไข</button>
                        <button type="button" wire:click="confirmDelete({{ $y->id }})" class="btn btn-outline btn-error btn-xs flex-1">ลบ</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow"><div class="card-body text-center text-base-content/60 py-8">ยังไม่มีปีการศึกษาในระบบ</div></div>
        @endforelse
    </div>

    {{-- Create/Edit modal --}}
    <div class="modal {{ $showFormModal ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ $editingId ? 'แก้ไขปีการศึกษา' : 'เพิ่มปีการศึกษาใหม่' }}</h3>
            <form wire:submit="save" class="space-y-4 mt-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ปีการศึกษา (พ.ศ.) *</span></label>
                    <input type="number" wire:model="year" class="input input-bordered w-full" placeholder="เช่น 2569">
                    @error('year') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">วันที่เริ่มต้น</span></label>
                        <input type="date" wire:model="start_date" class="input input-bordered w-full">
                    </div>
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">วันที่สิ้นสุด</span></label>
                        <input type="date" wire:model="end_date" class="input input-bordered w-full">
                        @error('end_date') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <label class="label cursor-pointer justify-start gap-2">
                    <input type="checkbox" wire:model="is_active" class="checkbox checkbox-sm">
                    <span class="label-text">ตั้งเป็นปีการศึกษาปัจจุบัน</span>
                </label>
                <p class="text-xs text-base-content/50 -mt-2">การตั้งเป็นปีปัจจุบันจะยกเลิกสถานะปัจจุบันของปีอื่นโดยอัตโนมัติ</p>

                <div class="modal-action">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="modal {{ $confirmingDeleteId ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">ยืนยันการลบปีการศึกษา</h3>
            <p class="py-4 text-sm text-base-content/70">การลบจะไม่สามารถกู้คืนได้ ยืนยันหรือไม่?</p>
            @if ($deleteError)
                <div class="alert alert-error text-sm mb-2">{{ $deleteError }}</div>
            @endif
            <div class="modal-action">
                <button type="button" wire:click="$set('confirmingDeleteId', null)" class="btn btn-ghost">ยกเลิก</button>
                <button type="button" wire:click="delete" class="btn btn-error">ลบข้อมูล</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('confirmingDeleteId', null)"></div>
    </div>
</div>
