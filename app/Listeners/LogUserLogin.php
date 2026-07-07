<?php

namespace App\Listeners;

use App\Support\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogUserLogin
{
    /**
     * Auto-discovered by Laravel via the handle() type-hint — do NOT also
     * register this with Event::listen() in a service provider, or it will
     * fire (and write) twice per login.
     */
    public function handle(Login $event): void
    {
        AuditLogger::log(
            action: 'login',
            module: 'ระบบยืนยันตัวตน',
            description: "{$event->user->name} เข้าสู่ระบบ",
            userId: $event->user->getKey(),
        );
    }
}
