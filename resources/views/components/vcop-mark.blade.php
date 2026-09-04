@props(['student', 'status' => null, 'canMark' => false, 'block' => false])

@php
    // สถานะการนำข้อมูลไปบันทึกในระบบ V-COP (ศูนย์กำลังคนอาชีวศึกษา) ซึ่งเจ้าหน้าที่
    // ต้องคีย์ด้วยมืออีกระบบหนึ่ง — ปุ่มนี้คือสิ่งที่กันไม่ให้คีย์ซ้ำหรือตกหล่น
    $state = $status['state'] ?? 'none';
@endphp

@if ($state === 'none')
    <span class="text-xs text-base-content/40">ยังไม่มีข้อมูลให้บันทึก</span>
@elseif ($state === 'done')
    <div @class(['flex items-center gap-2', 'flex-wrap' => $block, 'justify-end' => ! $block])>
        <span class="badge badge-success badge-sm gap-1 whitespace-nowrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            บันทึก V-COP แล้ว
        </span>
        @if ($canMark)
            <button type="button" wire:click="unmarkVcop({{ $student->id }})" class="btn btn-ghost btn-xs text-base-content/50">ยกเลิก</button>
        @endif
    </div>
    @if (($status['at'] ?? null))
        <p class="text-xs text-base-content/50 mt-0.5 {{ $block ? '' : 'text-right' }}">
            {{ $status['at']->format('d/m/').($status['at']->format('Y') + 543) }}
            @if (($status['by'] ?? null)) โดย {{ $status['by'] }} @endif
        </p>
    @endif
@elseif ($canMark)
    <button type="button" wire:click="markVcop({{ $student->id }})" class="btn btn-outline btn-primary btn-xs gap-1 {{ $block ? 'w-full' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        บันทึก V-COP แล้ว
    </button>
@else
    <span class="badge badge-ghost badge-sm whitespace-nowrap">ยังไม่ได้บันทึก</span>
@endif
