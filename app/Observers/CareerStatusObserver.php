<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\CareerStatus;
use App\Models\User;
use App\Notifications\NewCareerStatusSubmitted;
use Illuminate\Support\Facades\Notification;

class CareerStatusObserver
{
    /**
     * Notify every Admin whenever a new career-status record is submitted —
     * fulfils "แจ้งเตือนผู้ดูแลเมื่อมีข้อมูลใหม่" automatically, without
     * requiring staff to remember to tell anyone.
     */
    public function created(CareerStatus $careerStatus): void
    {
        $admins = User::where('role', UserRole::Admin->value)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewCareerStatusSubmitted($careerStatus));
        }
    }
}
