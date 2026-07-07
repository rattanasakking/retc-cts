<?php

namespace App\Livewire\AuditLogs;

use App\Enums\AuditAction;
use App\Exports\AuditLogExport;
use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('components.layouts.app')]
#[Title('บันทึกการใช้งานระบบ')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $userId = null;

    public string $module = '';

    public string $action = '';

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingModule(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo', 'userId', 'module', 'action']);
        $this->resetPage();
    }

    private function filteredQuery()
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
            ->latest();
    }

    public function exportLog()
    {
        return Excel::download(
            new AuditLogExport($this->search, $this->dateFrom, $this->dateTo, $this->userId, $this->module, $this->action),
            'audit_log_'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.audit-logs.index', [
            'logs' => $this->filteredQuery()->paginate(20),
            'users' => User::orderBy('name')->get(),
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditAction::cases(),
            'viewingLog' => $this->viewingId ? AuditLog::with('user')->find($this->viewingId) : null,
        ]);
    }
}
