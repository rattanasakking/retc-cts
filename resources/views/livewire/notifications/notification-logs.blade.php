<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">ประวัติการแจ้งเตือน</h1>
        <p class="text-sm text-base-content/60">บันทึกการแจ้งเตือนทุกช่องทางที่ระบบส่งออกไป (อีเมล / LINE)</p>
    </div>

    <div class="card bg-base-100 shadow">
        <div class="card-body p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <select wire:model.live="channel" class="select select-bordered select-sm">
                    <option value="">ทุกช่องทาง</option>
                    <option value="mail">อีเมล</option>
                    <option value="line">LINE</option>
                </select>
                <select wire:model.live="status" class="select select-bordered select-sm">
                    <option value="">ทุกสถานะ</option>
                    <option value="sent">สำเร็จ</option>
                    <option value="failed">ล้มเหลว</option>
                    <option value="skipped">ข้าม</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>ผู้รับ</th>
                        <th>ประเภทการแจ้งเตือน</th>
                        <th>ช่องทาง</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td>{{ $log->recipient_label }}</td>
                            <td>{{ $log->notification_label }}</td>
                            <td>
                                <span class="badge badge-outline badge-sm">
                                    {{ $log->channel === 'mail' ? 'อีเมล' : 'LINE' }}
                                </span>
                            </td>
                            <td>
                                <span @class([
                                    'badge badge-sm',
                                    'badge-success' => $log->status === 'sent',
                                    'badge-error' => $log->status === 'failed',
                                    'badge-ghost' => $log->status === 'skipped',
                                ])>
                                    {{ match($log->status) {
                                        'sent' => 'สำเร็จ',
                                        'failed' => 'ล้มเหลว',
                                        'skipped' => 'ข้าม',
                                        default => $log->status,
                                    } }}
                                </span>
                                @if ($log->error_message)
                                    <div class="tooltip" data-tip="{{ $log->error_message }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline text-error/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            <td class="text-xs text-base-content/60 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-base-content/60 py-8">ยังไม่มีประวัติการแจ้งเตือน</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $logs->links() }}</div>
</div>
