<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">จัดการข้อมูลนักศึกษา</h1>
            <p class="text-sm text-base-content/60">ค้นหา กรอง และจัดการข้อมูลนักศึกษาในระบบ</p>
        </div>
        <div class="flex gap-2">
            @if ($canManage)
                <a href="{{ route('students.import') }}" wire:navigate class="btn btn-outline btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    นำเข้า CSV
                </a>
                <button type="button" wire:click="openCreateModal" class="btn btn-primary btn-sm gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    เพิ่มนักศึกษา
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    {{-- Search & filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <label class="input input-bordered flex items-center gap-2 sm:col-span-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="ค้นหาชื่อ, รหัสนักศึกษา, เลขบัตรประชาชน..."
                        class="grow"
                    >
                </label>

                <select wire:model.live="filterAcademicYearId" class="select select-bordered">
                    <option value="">ทุกปีการศึกษา</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterStatus" class="select select-bordered">
                    <option value="">ทุกสถานะ</option>
                    <option value="studying">กำลังศึกษา</option>
                    <option value="graduated">จบการศึกษา</option>
                    <option value="dropped_out">ออกกลางคัน</option>
                </select>
            </div>

            @if ($search || $filterAcademicYearId || $filterStatus)
                <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs mt-2 w-fit">ล้างตัวกรองทั้งหมด</button>
            @endif
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="card bg-base-100 shadow hidden md:block">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>รหัสนักศึกษา</th>
                        <th>ชื่อ-สกุล</th>
                        <th>ปีการศึกษา</th>
                        <th>สาขาวิชา</th>
                        <th>สถานะ</th>
                        @if ($canManage)
                            <th class="text-right">จัดการ</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr wire:key="student-row-{{ $student->id }}">
                            <td class="font-mono text-sm">{{ $student->student_code }}</td>
                            <td>{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td>{{ $student->academicYear?->year }}</td>
                            <td>{{ $student->program ?: '—' }}</td>
                            <td>
                                <span @class([
                                    'badge badge-sm',
                                    'badge-info' => $student->status === 'studying',
                                    'badge-success' => $student->status === 'graduated',
                                    'badge-error' => $student->status === 'dropped_out',
                                ])>
                                    {{ match($student->status) {
                                        'studying' => 'กำลังศึกษา',
                                        'graduated' => 'จบการศึกษา',
                                        'dropped_out' => 'ออกกลางคัน',
                                        default => $student->status,
                                    } }}
                                </span>
                            </td>
                            @if ($canManage)
                                <td class="text-right space-x-1 whitespace-nowrap">
                                    <button type="button" wire:click="openEditModal({{ $student->id }})" class="btn btn-ghost btn-xs">แก้ไข</button>
                                    <button type="button" wire:click="confirmDelete({{ $student->id }})" class="btn btn-ghost btn-xs text-error">ลบ</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="text-center text-base-content/60 py-8">ไม่พบข้อมูลนักศึกษา</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($students as $student)
            <div class="card bg-base-100 shadow" wire:key="student-card-{{ $student->id }}">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</p>
                            <p class="text-xs text-base-content/60 font-mono">{{ $student->student_code }}</p>
                        </div>
                        <span @class([
                            'badge badge-sm shrink-0',
                            'badge-info' => $student->status === 'studying',
                            'badge-success' => $student->status === 'graduated',
                            'badge-error' => $student->status === 'dropped_out',
                        ])>
                            {{ match($student->status) {
                                'studying' => 'กำลังศึกษา',
                                'graduated' => 'จบการศึกษา',
                                'dropped_out' => 'ออกกลางคัน',
                                default => $student->status,
                            } }}
                        </span>
                    </div>
                    <div class="text-sm text-base-content/70 grid grid-cols-2 gap-1">
                        <span>ปีการศึกษา {{ $student->academicYear?->year }}</span>
                        <span class="text-right">{{ $student->program ?: '—' }}</span>
                    </div>
                    @if ($canManage)
                        <div class="flex gap-2 mt-2">
                            <button type="button" wire:click="openEditModal({{ $student->id }})" class="btn btn-outline btn-xs flex-1">แก้ไข</button>
                            <button type="button" wire:click="confirmDelete({{ $student->id }})" class="btn btn-outline btn-error btn-xs flex-1">ลบ</button>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center text-base-content/60 py-8">ไม่พบข้อมูลนักศึกษา</div>
            </div>
        @endforelse
    </div>

    <div>{{ $students->links() }}</div>

    {{-- Create / Edit modal --}}
    <div class="modal {{ $showFormModal ? 'modal-open' : '' }}">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg">{{ $editingId ? 'แก้ไขข้อมูลนักศึกษา' : 'เพิ่มนักศึกษาใหม่' }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2 grid grid-cols-3 gap-2">
                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">คำนำหน้า</span></label>
                            <select wire:model="prefix" class="select select-bordered select-sm w-full">
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                            </select>
                        </div>
                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">ชื่อ *</span></label>
                            <input type="text" wire:model="first_name" class="input input-bordered input-sm w-full">
                            @error('first_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label pb-1"><span class="label-text text-xs">นามสกุล *</span></label>
                            <input type="text" wire:model="last_name" class="input input-bordered input-sm w-full">
                            @error('last_name') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">รหัสนักศึกษา *</span></label>
                        <input type="text" wire:model="student_code" class="input input-bordered input-sm w-full">
                        @error('student_code') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">เลขบัตรประชาชน</span></label>
                        <input type="text" wire:model="national_id" maxlength="13" class="input input-bordered input-sm w-full">
                        @error('national_id') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ปีการศึกษา *</span></label>
                        <select wire:model="academic_year_id" class="select select-bordered select-sm w-full">
                            <option value="">— เลือกปีการศึกษา —</option>
                            @foreach ($academicYears as $year)
                                <option value="{{ $year->id }}">ปีการศึกษา {{ $year->year }}</option>
                            @endforeach
                        </select>
                        @error('academic_year_id') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">สถานะ *</span></label>
                        <select wire:model="status" class="select select-bordered select-sm w-full">
                            <option value="studying">กำลังศึกษา</option>
                            <option value="graduated">จบการศึกษา</option>
                            <option value="dropped_out">ออกกลางคัน</option>
                        </select>
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">สาขาวิชา</span></label>
                        <input type="text" wire:model="program" class="input input-bordered input-sm w-full">
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">ระดับการศึกษา</span></label>
                        <input type="text" wire:model="degree_level" class="input input-bordered input-sm w-full" placeholder="ปวช. / ปวส.">
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">เบอร์โทรศัพท์</span></label>
                        <input type="text" wire:model="phone" class="input input-bordered input-sm w-full">
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">อีเมล</span></label>
                        <input type="email" wire:model="email" class="input input-bordered input-sm w-full">
                        @error('email') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label pb-1"><span class="label-text text-xs">LINE User ID</span></label>
                        <input type="text" wire:model="line_user_id" class="input input-bordered input-sm w-full" placeholder="สำหรับรับแจ้งเตือนผ่าน LINE">
                        @error('line_user_id') <p class="text-xs text-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="label pb-1"><span class="label-text text-xs">ที่อยู่</span></label>
                        <textarea wire:model="address" class="textarea textarea-bordered textarea-sm w-full" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="closeModal" class="btn btn-ghost">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">บันทึก</span>
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" wire:click="closeModal"></div>
    </div>

    {{-- Delete confirmation modal --}}
    <div class="modal {{ $confirmingDeleteId ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">ยืนยันการลบข้อมูล</h3>
            <p class="py-4 text-sm text-base-content/70">คุณต้องการลบข้อมูลนักศึกษานี้ใช่หรือไม่? สามารถกู้คืนได้ภายหลังโดยผู้ดูแลระบบ</p>
            <div class="modal-action">
                <button type="button" wire:click="$set('confirmingDeleteId', null)" class="btn btn-ghost">ยกเลิก</button>
                <button type="button" wire:click="delete" class="btn btn-error">ลบข้อมูล</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('confirmingDeleteId', null)"></div>
    </div>
</div>
