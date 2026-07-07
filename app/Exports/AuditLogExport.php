<?php

namespace App\Exports;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $search = '',
        private readonly string $dateFrom = '',
        private readonly string $dateTo = '',
        private readonly ?int $userId = null,
        private readonly string $module = '',
        private readonly string $action = '',
    ) {
    }

    public function query(): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $term = '%'.$this->search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('description', 'like', $term)
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $term));
                });
            })
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->userId, fn ($query) => $query->where('user_id', $this->userId))
            ->when($this->module, fn ($query) => $query->where('module', $this->module))
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->orderByDesc('created_at');
    }

    public function headings(): array
    {
        return ['วันเวลา', 'ผู้ใช้งาน', 'การกระทำ', 'โมดูล', 'รายละเอียด', 'IP Address'];
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('d/m/Y H:i:s'),
            $log->user?->name ?? '(ไม่ทราบผู้ใช้งาน)',
            $log->action instanceof AuditAction ? $log->action->label() : $log->action,
            $log->module,
            $log->description,
            $log->ip_address,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
