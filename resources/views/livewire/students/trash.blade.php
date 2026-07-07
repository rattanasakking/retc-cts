<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">ถังขยะ - นักศึกษา</h1>
            <p class="text-sm text-base-content/60">ข้อมูลนักศึกษาที่ถูกลบ — กู้คืนได้ หรือลบถาวรเพื่อนำรหัสนักศึกษากลับมาใช้ซ้ำ</p>
        </div>
        <a href="{{ route('students.index') }}" wire:navigate class="btn btn-outline btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            กลับไปหน้ารายชื่อนักศึกษา
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success text-sm">{{ session('success') }}</div>
    @endif

    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <label class="input input-bordered flex items-center gap-2 max-w-md">
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
                        <th>ลบเมื่อ</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr wire:key="trashed-student-row-{{ $student->id }}">
                            <td class="font-mono text-sm">{{ $student->student_code }}</td>
                            <td>{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td>{{ $student->academicYear?->year }}</td>
                            <td class="whitespace-nowrap">{{ $student->deleted_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right space-x-1 whitespace-nowrap">
                                <button type="button" wire:click="restore({{ $student->id }})" class="btn btn-ghost btn-xs text-success">กู้คืน</button>
                                <button type="button" wire:click="confirmForceDelete({{ $student->id }})" class="btn btn-ghost btn-xs text-error">ลบถาวร</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-base-content/60 py-8">ถังขยะว่างเปล่า</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($students as $student)
            <div class="card bg-base-100 shadow" wire:key="trashed-student-card-{{ $student->id }}">
                <div class="card-body p-4 gap-2">
                    <div>
                        <p class="font-semibold">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</p>
                        <p class="text-xs text-base-content/60 font-mono">{{ $student->student_code }}</p>
                    </div>
                    <div class="text-sm text-base-content/70 grid grid-cols-2 gap-1">
                        <span>ปีการศึกษา {{ $student->academicYear?->year }}</span>
                        <span class="text-right">ลบเมื่อ {{ $student->deleted_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button type="button" wire:click="restore({{ $student->id }})" class="btn btn-outline btn-success btn-xs flex-1">กู้คืน</button>
                        <button type="button" wire:click="confirmForceDelete({{ $student->id }})" class="btn btn-outline btn-error btn-xs flex-1">ลบถาวร</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card bg-base-100 shadow">
                <div class="card-body text-center text-base-content/60 py-8">ถังขยะว่างเปล่า</div>
            </div>
        @endforelse
    </div>

    <div>{{ $students->links() }}</div>

    {{-- Force-delete confirmation modal --}}
    <div class="modal {{ $confirmingForceDeleteId ? 'modal-open' : '' }}">
        <div class="modal-box">
            <h3 class="font-bold text-lg">ยืนยันการลบถาวร</h3>
            <p class="py-4 text-sm text-base-content/70">
                ข้อมูลนักศึกษานี้จะถูกลบออกจากระบบอย่างถาวร <span class="font-semibold text-error">ไม่สามารถกู้คืนได้อีก</span>
                แต่รหัสนักศึกษา/เลขบัตรประชาชนจะสามารถนำมาใช้ซ้ำได้อีกครั้ง คุณแน่ใจหรือไม่?
            </p>
            <div class="modal-action">
                <button type="button" wire:click="$set('confirmingForceDeleteId', null)" class="btn btn-ghost">ยกเลิก</button>
                <button type="button" wire:click="forceDelete" class="btn btn-error">ลบถาวร</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="$set('confirmingForceDeleteId', null)"></div>
    </div>
</div>
