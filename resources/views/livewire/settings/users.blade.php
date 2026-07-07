<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">จัดการผู้ใช้งาน</h1>
            <p class="text-sm text-base-content/60">เพิ่ม แก้ไข ลบผู้ใช้งาน และเปลี่ยนรหัสผ่าน</p>
        </div>
        <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            เพิ่มผู้ใช้งาน
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    @if ($actionError && ! $confirmingDeleteId)
        <div class="alert alert-error text-sm">{{ $actionError }}</div>
    @endif

    {{-- Desktop table --}}
    <div class="card bg-base-100 shadow hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>ชื่อ</th>
                        <th>อีเมล</th>
                        <th>สิทธิ์</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-row-{{ $user->id }}">
                            <td class="font-medium">
                                {{ $user->name }}
                                @if ($user->id === auth()->id())
                                    <span class="badge badge-ghost badge-xs ml-1">คุณ</span>
                                @endif
                            </td>
                            <td class="text-sm text-base-content/70">{{ $user->email }}</td>
                            <td><span class="badge badge-outline badge-sm badge-primary">{{ $user->role->label() }}</span></td>
                            <td class="text-right space-x-1 whitespace-nowrap">
                                <button type="button" wire:click="openEditModal({{ $user->id }})" class="btn btn-ghost btn-xs">แก้ไข</button>
                                <button type="button" wire:click="openPasswordModal({{ $user->id }})" class="btn btn-ghost btn-xs">เปลี่ยนรหัสผ่าน</button>
                                <button type="button" wire:click="confirmDelete({{ $user->id }})" class="btn btn-ghost btn-xs text-error">ลบ</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @foreach ($users as $user)
            <div class="card bg-base-100 shadow" wire:key="user-card-{{ $user->id }}">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $user->name }} @if ($user->id === auth()->id()) <span class="badge badge-ghost badge-xs">คุณ</span> @endif</p>
                            <p class="text-xs text-base-content/60">{{ $user->email }}</p>
                        </div>
                        <span class="badge badge-outline badge-sm badge-primary shrink-0">{{ $user->role->label() }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button type="button" wire:click="openEditModal({{ $user->id }})" class="btn btn-outline btn-xs flex-1">แก้ไข</button>
                        <button type="button" wire:click="openPasswordModal({{ $user->id }})" class="btn btn-outline btn-xs flex-1">รหัสผ่าน</button>
                        <button type="button" wire:click="confirmDelete({{ $user->id }})" class="btn btn-outline btn-error btn-xs flex-1">ลบ</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Create/Edit modal --}}
    <div class="modal {{ $showFormModal ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ $editingId ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งานใหม่' }}</h3>

            @if ($actionError && $showFormModal)
                <div class="alert alert-error text-sm mt-3">{{ $actionError }}</div>
            @endif

            <form wire:submit="save" class="space-y-4 mt-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ชื่อ-สกุล *</span></label>
                    <input type="text" wire:model="name" class="input input-bordered w-full">
                    @error('name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">อีเมล *</span></label>
                    <input type="email" wire:model="email" class="input input-bordered w-full">
                    @error('email') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">สิทธิ์การใช้งาน *</span></label>
                    <select wire:model="role" class="select select-bordered w-full">
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label pb-1"><span class="label-text text-xs">LINE User ID (สำหรับรับแจ้งเตือนผ่าน LINE)</span></label>
                    <input type="text" wire:model="line_user_id" class="input input-bordered w-full" placeholder="เช่น U4af4980629...">
                    @error('line_user_id') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>

                @unless ($editingId)
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">รหัสผ่าน *</span></label>
                        <input type="password" wire:model="password" class="input input-bordered w-full">
                        @error('password') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ยืนยันรหัสผ่าน *</span></label>
                        <input type="password" wire:model="password_confirmation" class="input input-bordered w-full">
                    </div>
                @endunless

                <div class="modal-action">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>

    {{-- Change password modal --}}
    <div class="modal {{ $passwordUserId ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">เปลี่ยนรหัสผ่าน</h3>
            <form wire:submit="updatePassword" class="space-y-4 mt-4">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">รหัสผ่านใหม่ *</span></label>
                    <input type="password" wire:model="newPassword" class="input input-bordered w-full">
                    @error('newPassword') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ยืนยันรหัสผ่านใหม่ *</span></label>
                    <input type="password" wire:model="newPassword_confirmation" class="input input-bordered w-full">
                </div>
                <div class="modal-action">
                    <button type="button" wire:click="closePasswordModal" class="btn btn-ghost">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">เปลี่ยนรหัสผ่าน</button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closePasswordModal"></div>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="modal {{ $confirmingDeleteId ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">ยืนยันการลบผู้ใช้งาน</h3>
            <p class="py-4 text-sm text-base-content/70">การลบจะไม่สามารถกู้คืนได้ ยืนยันหรือไม่?</p>
            <div class="modal-action">
                <button type="button" wire:click="$set('confirmingDeleteId', null)" class="btn btn-ghost">ยกเลิก</button>
                <button type="button" wire:click="delete" class="btn btn-error">ลบข้อมูล</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('confirmingDeleteId', null)"></div>
    </div>
</div>
