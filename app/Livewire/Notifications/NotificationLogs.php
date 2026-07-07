<?php

namespace App\Livewire\Notifications;

use App\Models\NotificationLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('ประวัติการแจ้งเตือน')]
class NotificationLogs extends Component
{
    use WithPagination;

    public string $channel = '';

    public string $status = '';

    public function updatingChannel(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = NotificationLog::query()
            ->with('notifiable')
            ->when($this->channel, fn ($q) => $q->where('channel', $this->channel))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(20);

        return view('livewire.notifications.notification-logs', [
            'logs' => $logs,
        ]);
    }
}
