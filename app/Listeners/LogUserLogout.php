<?php

namespace App\Listeners;

use App\Support\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
    /**
     * Auto-discovered by Laravel via the handle() type-hint — do NOT also
     * register this with Event::listen() in a service provider, or it will
     * fire (and write) twice per logout.
     */
    public function handle(Logout $event): void
    {
        // $event->user can be null if the guard had no authenticated user
        // (e.g. logging out an already-expired session).
        if (! $event->user) {
            return;
        }

        AuditLogger::log(
            action: 'logout',
            module: 'ระบบยืนยันตัวตน',
            description: "{$event->user->name} ออกจากระบบ",
            userId: $event->user->getKey(),
        );
    }
}
