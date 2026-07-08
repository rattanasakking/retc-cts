import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
window.L = L;

const THAI_MONTHS = [
    'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม',
];
const THAI_WEEKDAYS = ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'];

document.addEventListener('alpine:init', () => {
    Alpine.data('thaiDateInput', ($wire, prop, yearsBack, yearsForward) => ({
        open: false,
        isoValue: $wire.entangle(prop),
        viewYear: null,
        viewMonth: null,
        thaiMonths: THAI_MONTHS,
        weekdayLabels: THAI_WEEKDAYS,

        init() {
            const base = this.isoValue ? this.parseIso(this.isoValue) : new Date();
            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
        },

        parseIso(iso) {
            const [y, m, d] = iso.split('-').map(Number);
            return new Date(y, m - 1, d);
        },

        pad(n) {
            return String(n).padStart(2, '0');
        },

        toIso(y, m, d) {
            return `${y}-${this.pad(m + 1)}-${this.pad(d)}`;
        },

        get displayValue() {
            if (!this.isoValue) return '';
            const d = this.parseIso(this.isoValue);

            return `${this.pad(d.getDate())}/${this.pad(d.getMonth() + 1)}/${d.getFullYear() + 543}`;
        },

        get yearOptions() {
            const current = new Date().getFullYear();
            const years = [];

            for (let y = current - yearsBack; y <= current + yearsForward; y++) {
                years.push(y);
            }

            return years;
        },

        get calendarDays() {
            const startOffset = new Date(this.viewYear, this.viewMonth, 1).getDay();
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const cells = [];

            for (let i = 0; i < startOffset; i++) {
                cells.push({ key: `blank-${i}`, day: null });
            }

            for (let day = 1; day <= daysInMonth; day++) {
                cells.push({ key: `day-${day}`, day });
            }

            return cells;
        },

        isSelected(day) {
            if (!this.isoValue || !day) return false;
            const d = this.parseIso(this.isoValue);

            return d.getFullYear() === this.viewYear && d.getMonth() === this.viewMonth && d.getDate() === day;
        },

        isToday(day) {
            if (!day) return false;
            const t = new Date();

            return t.getFullYear() === this.viewYear && t.getMonth() === this.viewMonth && t.getDate() === day;
        },

        toggle() {
            this.open = !this.open;
            if (this.open) this.init();
        },

        prevMonth() {
            if (this.viewMonth === 0) {
                this.viewMonth = 11;
                this.viewYear--;
            } else {
                this.viewMonth--;
            }
        },

        nextMonth() {
            if (this.viewMonth === 11) {
                this.viewMonth = 0;
                this.viewYear++;
            } else {
                this.viewMonth++;
            }
        },

        selectDay(day) {
            if (!day) return;
            this.isoValue = this.toIso(this.viewYear, this.viewMonth, day);
            this.open = false;
        },

        selectToday() {
            const t = new Date();
            this.viewYear = t.getFullYear();
            this.viewMonth = t.getMonth();
            this.isoValue = this.toIso(t.getFullYear(), t.getMonth(), t.getDate());
            this.open = false;
        },

        clear() {
            this.isoValue = '';
            this.open = false;
        },
    }));
});
