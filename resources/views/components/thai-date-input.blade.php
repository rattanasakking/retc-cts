@props(['wireModel', 'yearsBack' => 10, 'yearsForward' => 2])

<div x-data="thaiDateInput($wire, @js($wireModel), {{ (int) $yearsBack }}, {{ (int) $yearsForward }})" class="relative">
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
        <div class="flex items-center justify-between mb-2 gap-1">
            <button type="button" @click="prevMonth()" class="btn btn-ghost btn-xs" aria-label="เดือนก่อนหน้า">‹</button>
            <div class="flex items-center gap-1 text-sm font-semibold">
                <span x-text="thaiMonths[viewMonth]"></span>
                <select x-model.number="viewYear" class="select select-ghost select-xs">
                    <template x-for="y in yearOptions" :key="y">
                        <option :value="y" x-text="y + 543"></option>
                    </template>
                </select>
            </div>
            <button type="button" @click="nextMonth()" class="btn btn-ghost btn-xs" aria-label="เดือนถัดไป">›</button>
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
