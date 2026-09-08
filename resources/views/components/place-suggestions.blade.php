@props(['field', 'suggestions' => [], 'searched' => false, 'kind' => 'company'])

@php
    $missing = $kind === 'institution' ? 'ไม่พบชื่อสถานศึกษานี้' : 'ไม่พบชื่อสถานประกอบการนี้';
@endphp

{{-- รายชื่อจากฐานข้อมูลของระบบและจากแผนที่รวมกันในลิสต์เดียว ผู้กรอกจึงไม่ต้อง
     รู้ว่าชื่อไหนมาจากไหน แค่กดเลือกอันที่ใช่ --}}
<div wire:loading wire:target="{{ $field }}" class="mt-2 flex items-center gap-2 text-xs text-base-content/50">
    <span class="loading loading-spinner loading-xs"></span>
    กำลังค้นหา...
</div>

<div wire:loading.remove wire:target="{{ $field }}">
    @if ($suggestions !== [])
        <ul class="mt-2 space-y-1 max-h-64 overflow-y-auto">
            @foreach ($suggestions as $index => $suggestion)
                <li>
                    <button
                        type="button"
                        wire:click="usePlaceSuggestion('{{ $field }}', {{ $index }})"
                        wire:key="{{ $field }}-suggestion-{{ $index }}"
                        class="w-full text-left rounded-box border border-base-300 px-3 py-2 hover:bg-base-200 transition"
                    >
                        <span class="flex items-start justify-between gap-2">
                            <span class="font-medium text-sm">{{ $suggestion['name'] }}</span>
                            <span @class([
                                'badge badge-xs shrink-0 mt-1',
                                'badge-primary' => $suggestion['source'] === 'local',
                                'badge-ghost' => $suggestion['source'] !== 'local',
                            ])>
                                {{ $suggestion['source'] === 'local' ? 'ในระบบ' : 'จากแผนที่' }}
                            </span>
                        </span>
                        @if ($suggestion['detail'])
                            <span class="block text-xs text-base-content/50 truncate">{{ $suggestion['detail'] }}</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>

        @if (collect($suggestions)->contains(fn ($suggestion) => $suggestion['source'] === 'osm'))
            <p class="text-[0.65rem] text-base-content/40 mt-1">ข้อมูลสถานที่บางรายการจาก OpenStreetMap (ODbL)</p>
        @endif
    @elseif ($searched)
        <p class="text-xs text-base-content/50 mt-2">{{ $missing }} — พิมพ์ชื่อเต็มได้เลย ระบบจะบันทึกไว้ให้</p>
    @endif
</div>
