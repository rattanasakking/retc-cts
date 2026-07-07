<?php

use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\CareerStatuses\CareerStatusForm;
use App\Livewire\Dashboard;
use App\Livewire\Notifications\NotificationLogs;
use App\Livewire\Notifications\SendReminders;
use App\Livewire\Public\StudentSearch;
use App\Livewire\Reports\ExportCenter;
use App\Livewire\Settings\AcademicYears as SettingsAcademicYears;
use App\Livewire\Settings\Backup as SettingsBackup;
use App\Livewire\Settings\SystemInformation as SettingsSystemInformation;
use App\Livewire\Settings\Users as SettingsUsers;
use App\Livewire\Students\Index as StudentsIndex;
use App\Livewire\Students\StudentImporter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('public.student-search');
});

Route::get('/search', StudentSearch::class)
    ->middleware('throttle:30,1')
    ->name('public.student-search');

Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware('auth')
    ->name('profile');

Route::get('/students', StudentsIndex::class)
    ->middleware('auth')
    ->name('students.index');

Route::middleware(['auth', 'role:admin,teacher,department_head'])
    ->get('/career-statuses/create', CareerStatusForm::class)
    ->name('career-statuses.create');

Route::middleware(['auth', 'role:admin,department_head'])->group(function () {
    Route::get('/students/import', StudentImporter::class)->name('students.import');

    Route::get('/students/import/template', function () {
        $csv = "\xEF\xBB\xBF".implode(',', [
            'student_code', 'national_id', 'prefix', 'first_name', 'last_name',
            'academic_year', 'program', 'degree_level', 'phone', 'email', 'status',
        ])."\n";
        $csv .= implode(',', [
            '67-00001', '1234567890123', 'นาย', 'สมชาย', 'ใจดี',
            '2569', 'เทคโนโลยีสารสนเทศ', 'ปวส.', '0812345678', 'somchai@example.com', 'graduated',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students_import_template.csv"',
        ]);
    })->name('students.import.template');
});

Route::middleware(['auth', 'role:admin,executive,department_head'])
    ->get('/reports/export', ExportCenter::class)
    ->name('reports.export');

Route::middleware(['auth', 'role:admin,teacher,department_head'])
    ->get('/notifications/reminders', SendReminders::class)
    ->name('notifications.reminders');

Route::middleware(['auth', 'role:admin'])
    ->get('/notifications/logs', NotificationLogs::class)
    ->name('notifications.logs');

Route::middleware(['auth', 'role:admin'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/academic-years', SettingsAcademicYears::class)->name('academic-years');
    Route::get('/system', SettingsSystemInformation::class)->name('system');
    Route::get('/users', SettingsUsers::class)->name('users');
    Route::get('/backup', SettingsBackup::class)->name('backup');
});

Route::middleware(['auth', 'role:admin'])
    ->get('/audit-logs', AuditLogsIndex::class)
    ->name('audit-logs.index');

require __DIR__.'/auth.php';
