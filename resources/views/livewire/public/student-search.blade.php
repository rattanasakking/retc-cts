<div class="space-y-6">
    <div class="text-center space-y-1">
        <h1 class="text-2xl font-bold">ค้นหาข้อมูลนักศึกษา</h1>
        <p class="text-sm text-base-content/60">ตรวจสอบสถานะการศึกษาด้วยชื่อ-นามสกุล หรือรหัสนักศึกษา</p>
    </div>

    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <label class="input input-bordered flex items-center gap-2 text-base">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="พิมพ์ชื่อ, นามสกุล หรือรหัสนักศึกษา..."
                    class="grow"
                    autofocus
                >
                <span wire:loading wire:target="search" class="loading loading-spinner loading-sm"></span>
            </label>
            <p class="text-xs text-base-content/50 mt-1">พิมพ์อย่างน้อย 2 ตัวอักษรเพื่อเริ่มค้นหา</p>
        </div>
    </div>

    @if (! $hasSearched)
        <div class="text-center py-12 text-base-content/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75l-2.489-2.489m0 0a3.375 3.375 0 10-4.773-4.773 3.375 3.375 0 004.774 4.774zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm">กรอกชื่อหรือรหัสนักศึกษาด้านบนเพื่อเริ่มค้นหา</p>
        </div>
    @elseif ($students->isEmpty())
        <div class="text-center py-12 text-base-content/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75c0-1.036 1.007-1.875 2.25-1.875S14.25 8.714 14.25 9.75c0 .84-.658 1.551-1.563 1.795-.581.156-1.187.622-1.187 1.155v.15M12 15h.007v.008H12V15z" />
            </svg>
            <p class="text-sm">ไม่พบข้อมูลนักศึกษาที่ตรงกับ "{{ $term }}"</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($students as $student)
                <div class="card bg-base-100 shadow" wire:key="result-{{ $student->id }}">
                    <div class="card-body p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $student->prefix }}{{ $student->first_name }} {{ $student->last_name }}</p>
                                <p class="text-xs text-base-content/50 font-mono">{{ $student->student_code }}</p>
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
                        <div class="text-sm text-base-content/70 grid grid-cols-2 gap-1 mt-1">
                            <span>{{ $student->program ?: '—' }} @if($student->degree_level) ({{ $student->degree_level }}) @endif</span>
                            <span class="text-right">ปีการศึกษา {{ $student->academicYear?->year }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $students->links() }}</div>
    @endif
</div>
