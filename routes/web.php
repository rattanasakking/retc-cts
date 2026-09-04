<?php

use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\CareerStatuses\CareerStatusForm;
use App\Livewire\Dashboard;
use App\Livewire\Notifications\NotificationLogs;
use App\Livewire\Notifications\SendReminders;
use App\Livewire\Public\CareerStatusSelfReport;
use App\Livewire\Public\StudentSearch;
use App\Livewire\Reports\CareerStatusReport;
use App\Livewire\Reports\ExportCenter;
use App\Livewire\Reports\SelfReportUsage;
use App\Livewire\Settings\AcademicYears as SettingsAcademicYears;
use App\Livewire\Settings\Backup as SettingsBackup;
use App\Livewire\Settings\SystemInformation as SettingsSystemInformation;
use App\Livewire\Settings\Users as SettingsUsers;
use App\Livewire\Students\Index as StudentsIndex;
use App\Livewire\Students\RecentlyUpdated as StudentsRecentlyUpdated;
use App\Livewire\Students\Show as StudentsShow;
use App\Livewire\Students\StudentImporter;
use App\Livewire\Students\Trash as StudentsTrash;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

// Visitors land on the self-report form: almost everyone reaching this site
// without an account is a graduate coming to report their own status, usually
// from a link or a QR code, so that is the first thing they should see.
// Staff who are already signed in still go straight to the dashboard.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('public.career-status-self-report');
});

// Served from the database rather than a static public/manifest.json so the
// name a college sets in ตั้งค่าระบบ is what appears when the app is added to
// a phone's home screen.
Route::get('/manifest.webmanifest', function () {
    $branding = SystemSetting::cached();

    return response()->json([
        'name' => $branding->displayName(),
        'short_name' => $branding->displayShortName(),
        'description' => 'ระบบติดตามภาวะการมีงานทำของผู้สำเร็จการศึกษา',
        'start_url' => '/dashboard',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => '#121212',
        'theme_color' => $branding->brandColor(),
        'lang' => 'th',
        'icons' => [
            ['src' => '/icons/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
            ['src' => '/icons/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'maskable'],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

Route::get('/search', StudentSearch::class)
    ->middleware('throttle:30,1')
    ->name('public.student-search');

Route::get('/report-status', CareerStatusSelfReport::class)
    ->middleware('throttle:20,1')
    ->name('public.career-status-self-report');

Route::get('/dashboard', Dashboard::class)
    ->middleware('auth')
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware('auth')
    ->name('profile');

Route::get('/students', StudentsIndex::class)
    ->middleware('auth')
    ->name('students.index');

// Must stay above the /students/{student} route below, otherwise the
// wildcard swallows it and route model binding 404s on "recently-updated".
Route::get('/students/recently-updated', StudentsRecentlyUpdated::class)
    ->middleware('auth')
    ->name('students.recently-updated');

Route::middleware(['auth', 'role:admin,teacher,department_head'])
    ->get('/career-statuses/create', CareerStatusForm::class)
    ->name('career-statuses.create');

Route::middleware(['auth', 'role:admin,department_head'])->group(function () {
    Route::get('/students/import', StudentImporter::class)->name('students.import');
    Route::get('/students/trash', StudentsTrash::class)->name('students.trash');

    Route::get('/students/import/template', function () {
        $csv = "\xEF\xBB\xBF".implode(',', [
            'student_code', 'national_id', 'prefix', 'first_name', 'last_name', 'birth_date',
            'academic_year', 'program', 'degree_level', 'phone', 'email', 'status',
        ])."\n";
        $csv .= implode(',', [
            '67-00001', '1234567890123', 'นาย', 'สมชาย', 'ใจดี', '2007-10-02',
            '2569', 'เทคโนโลยีสารสนเทศ', 'ปวส.', '0812345678', 'somchai@example.com', 'graduated',
        ])."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students_import_template.csv"',
        ]);
    })->name('students.import.template');
});

Route::get('/students/{student}', StudentsShow::class)
    ->middleware('auth')
    ->name('students.show');

Route::middleware(['auth', 'role:admin,executive,department_head'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/career-status', CareerStatusReport::class)->name('career-status');
    Route::get('/self-report-usage', SelfReportUsage::class)->name('self-report-usage');
    Route::get('/export', ExportCenter::class)->name('export');
});

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
