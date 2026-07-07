<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">บันทึกการใช้งานระบบ</h1>
            <p class="text-sm text-base-content/60">ประวัติการเข้าสู่ระบบ นำเข้า/ส่งออกข้อมูล และการสร้าง/แก้ไข/ลบข้อมูลทั้งหมด</p>
        </div>
        <button type="button" wire:click="exportLog" wire:loading.attr="disabled" wire:target="exportLog" class="btn btn-primary btn-sm gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
            </svg>
            <span wire:loading.remove wire:target="exportLog">ส่งออก Log</span>
            <span wire:loading wire:target="exportLog">กำลังส่งออก...</span>
        </button>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4 space-y-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="ค้นหาจากรายละเอียด หรือชื่อผู้ใช้งาน..." class="input input-bordered w-full">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">จากวันที่</span></label>
                    <input type="date" wire:model.live="dateFrom" class="input input-bordered input-sm w-full">
                </div>
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ถึงวันที่</span></label>
                    <input type="date" wire:model.live="dateTo" class="input input-bordered input-sm w-full">
                </div>
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">ผู้ใช้งาน</span></label>
                    <select wire:model.live="userId" class="select select-bordered select-sm w-full">
                        <option value="">ทุกคน</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">โมดูล</span></label>
                    <select wire:model.live="module" class="select select-bordered select-sm w-full">
                        <option value="">ทุกโมดูล</option>
                        @foreach ($modules as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label pb-1"><span class="label-text text-xs">การกระทำ</span></label>
                    <select wire:model.live="action" class="select select-bordered select-sm w-full">
                        <option value="">ทุกการกระทำ</option>
                        @foreach ($actions as $a)
                            <option value="{{ $a->value }}">{{ $a->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($search || $dateFrom || $dateTo || $userId || $module || $action)
                <div>
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs">ล้างตัวกรองทั้งหมด</button>
                </div>
            @endif
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="card bg-base-100 shadow hidden md:block">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>เวลา</th>
                        <th>ผู้ใช้งาน</th>
                        <th>การกระทำ</th>
                        <th>โมดูล</th>
                        <th>รายละเอียด</th>
                        <th class="text-right">รายละเอียดเพิ่มเติม</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="log-row-{{ $log->id }}">
                            <td class="text-xs text-base-content/60 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-sm">{{ $log->user?->name ?? '(ไม่ทราบผู้ใช้งาน)' }}</td>
                            <td>
                                <span class="badge badge-sm {{ $log->action->badgeClass() }}">{{ $log->action->label() }}</span>
                            </td>
                            <td class="text-sm">{{ $log->module }}</td>
                            <td class="text-sm max-w-xs truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                            <td class="text-right">
                                @if ($log->old_values || $log->new_values)
                                    <button type="button" wire:click="viewDetails({{ $log->id }})" class="btn btn-ghost btn-xs">ดูข้อมูล</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-base-content/60 py-8">ไม่พบบันทึกการใช้งานที่ตรงกับเงื่อนไข</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @forelse ($logs as $log)
            <div class="card bg-base-100 shadow" wire:key="log-card-{{ $log->id }}">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-sm">{{ $log->user?->name ?? '(ไม่ทราบผู้ใช้งาน)' }}</p>
                            <p class="text-xs text-base-content/60">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <span class="badge badge-sm {{ $log->action->badgeClass() }} shrink-0">{{ $log->action->label() }}</span>
                    </div>
                    <p class="text-xs text-base-content/60">{{ $log->module }}</p>
                    <p class="text-sm">{{ $log->description }}</p>
                    @if ($log->old_values || $log->new_values)
                        <button type="button" wire:click="viewDetails({{ $log->id }})" class="btn btn-outline btn-xs self-start">ดูข้อมูล</button>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-base-content/60 py-8">ไม่พบบันทึกการใช้งานที่ตรงกับเงื่อนไข</div>
        @endforelse
    </div>

    <div>{{ $logs->links() }}</div>

    {{-- Details modal --}}
    <div class="modal {{ $viewingLog ? 'modal-open' : '' }}">
        <div class="modal-box max-w-2xl">
            <h3 class="font-bold text-lg">รายละเอียดการเปลี่ยนแปลง</h3>
            @if ($viewingLog)
                <p class="text-sm text-base-content/60 mb-4">{{ $viewingLog->description }}</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-2">ค่าเดิม</p>
                        @if ($viewingLog->old_values)
                            <div class="overflow-x-auto">
                                <table class="table table-xs">
                                    @foreach ($viewingLog->old_values as $key => $value)
                                        <tr>
                                            <th class="whitespace-nowrap">{{ $key }}</th>
                                            <td class="break-all">{{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-base-content/40">— ไม่มี —</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-2">ค่าใหม่</p>
                        @if ($viewingLog->new_values)
                            <div class="overflow-x-auto">
                                <table class="table table-xs">
                                    @foreach ($viewingLog->new_values as $key => $value)
                                        <tr>
                                            <th class="whitespace-nowrap">{{ $key }}</th>
                                            <td class="break-all">{{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-base-content/40">— ไม่มี —</p>
                        @endif
                    </div>
                </div>
            @endif
            <div class="modal-action">
                <button type="button" wire:click="closeDetails" class="btn btn-ghost">ปิด</button>
            </div>
        </div>
        <div class="modal-backdrop" wire:click="closeDetails"></div>
    </div>
</div>
