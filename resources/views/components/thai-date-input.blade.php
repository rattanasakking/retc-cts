@props(['wireModel', 'yearsBack' => 10, 'yearsForward' => 2, 'defaultYear' => null])

@php
    // The year <option>s are rendered server-side on purpose. Built with x-for
    // instead, Alpine binds x-model to the <select> before the options exist,
    // the browser finds no matching value and falls back to displaying the
    // first one — so the picker showed พ.ศ. 2489 while the calendar underneath
    // was really on the current year.
    $currentYear = (int) now()->format('Y');
    $firstYear = $currentYear - (int) $yearsBack;
    $lastYear = $currentYear + (int) $yearsForward;

    // defaultYear is given in พ.ศ.; the component works in ค.ศ. throughout.
    $defaultYearAd = $defaultYear !== null
        ? min(max((int) $defaultYear - 543, $firstYear), $lastYear)
        : null;

    // Same reasoning for the months: rendered here so the <select> has its
    // options before Alpine binds to it.
    $thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
    ];
@endphp

<div x-data="thaiDateInput($wire, @js($wireModel), {{ $defaultYearAd ?? 'null' }})" class="relative">
    <input
        type="text"
        readonly
        x-bind:value="displayValue"
        @click="toggle()"
        {{ $attributes->merge(['class' => 'input input-bordered w-full cursor-pointer']) }}
        placeholder="วว/ดด/ปปปป (พ.ศ.)"
    >

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition.opacity.duration.100ms
        class="absolute z-20 mt-1 w-72 bg-base-100 rounded-box shadow-lg border border-base-300 p-3"
    >
        {{-- เดือนและปีเป็น dropdown ทั้งคู่ กระโดดไปเดือนที่ต้องการได้ทันที
             ไม่ต้องกดลูกศรทีละเดือน --}}
        <div class="flex items-center gap-1 mb-3">
            <button type="button" @click="prevMonth()" class="btn btn-ghost btn-sm btn-square shrink-0" aria-label="เดือนก่อนหน้า">‹</button>

            <select x-model.number="viewMonth" class="select select-bordered select-sm flex-1 min-w-0 font-semibold" aria-label="เดือน">
                @foreach ($thaiMonths as $index => $month)
                    <option value="{{ $index }}">{{ $month }}</option>
                @endforeach
            </select>

            <select x-model.number="viewYear" class="select select-bordered select-sm w-24 shrink-0 font-semibold" aria-label="ปี พ.ศ.">
                @for ($year = $firstYear; $year <= $lastYear; $year++)
                    <option value="{{ $year }}">{{ $year + 543 }}</option>
                @endfor
            </select>

            <button type="button" @click="nextMonth()" class="btn btn-ghost btn-sm btn-square shrink-0" aria-label="เดือนถัดไป">›</button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs text-base-content/50 mb-1">
            <template x-for="d in weekdayLabels" :key="d">
                <span x-text="d"></span>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-sm">
            <template x-for="cell in calendarDays" :key="cell.key">
                <div>
                    <button
                        type="button"
                        x-show="cell.day !== null"
                        x-text="cell.day"
                        @click="selectDay(cell.day)"
                        class="w-full rounded-btn py-1"
                        :class="isSelected(cell.day) ? 'bg-primary text-primary-content font-semibold' : (isToday(cell.day) ? 'border border-primary' : 'hover:bg-base-200')"
                    ></button>
                </div>
            </template>
        </div>

        <div class="flex justify-between mt-2 pt-2 border-t border-base-200">
            <button type="button" @click="selectToday()" class="btn btn-ghost btn-xs">วันนี้</button>
            <button type="button" @click="clear()" class="btn btn-ghost btn-xs text-error">ล้างค่า</button>
        </div>
    </div>
</div>
