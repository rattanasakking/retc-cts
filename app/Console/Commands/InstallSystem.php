<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\ThaiGeographySeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * First-run setup for a fresh clone — the steps another college would
 * otherwise have to run one at a time from a checklist, in an order that
 * matters (a key before anything encrypts, tables before the seeder, the
 * seeder before the location pickers work).
 *
 * Deliberately safe to run twice: the geography seeder upserts, and an
 * existing admin is left alone rather than duplicated.
 */
class InstallSystem extends Command
{
    protected $signature = 'app:install
                            {--admin-name= : ชื่อผู้ดูแลระบบคนแรก}
                            {--admin-email= : อีเมลผู้ดูแลระบบคนแรก}
                            {--admin-password= : รหัสผ่านผู้ดูแลระบบคนแรก}
                            {--college= : ชื่อวิทยาลัย}
                            {--no-interaction-safe : ข้ามขั้นตอนที่ต้องถาม เมื่อไม่ได้ส่งค่ามาทางออปชัน}';

    protected $description = 'ติดตั้งระบบครั้งแรก — สร้างตาราง ข้อมูลพื้นฐาน และบัญชีผู้ดูแลระบบ';

    public function handle(): int
    {
        $this->info('เริ่มติดตั้ง RETC-CTS');
        $this->newLine();

        if (! $this->ensureAppKey()) {
            return self::FAILURE;
        }

        $this->components->task('สร้างตารางฐานข้อมูล', function () {
            Artisan::call('migrate', ['--force' => true]);

            return true;
        });

        $this->components->task('นำเข้าข้อมูลจังหวัด/อำเภอ/ตำบล', function () {
            Artisan::call('db:seed', [
                '--class' => ThaiGeographySeeder::class,
                '--force' => true,
            ]);

            return true;
        });

        $this->components->task('เชื่อมโฟลเดอร์ไฟล์แนบ (storage:link)', function () {
            try {
                if (! app()->runningUnitTests() && ! file_exists(public_path('storage'))) {
                    Artisan::call('storage:link');
                }

                return true;
            } catch (Throwable) {
                // Shared hosting sometimes forbids symlinks; the app runs fine
                // without one, only uploaded logos stop resolving.
                return false;
            }
        });

        $this->newLine();
        $this->createFirstAdmin();
        $this->setCollegeName();

        $this->newLine();
        $this->info('ติดตั้งเสร็จแล้ว');
        $this->line('ขั้นตอนที่เหลือ:');
        $this->line('  1. รัน php artisan optimize เมื่อแก้ .env เสร็จแล้ว');
        $this->line('  2. ตั้ง cron: * * * * * php '.base_path('artisan').' schedule:run');
        $this->line('  3. ตั้ง cron ระบายคิวทุก 5 นาที: php '.base_path('artisan').' queue:work --stop-when-empty');
        $this->line('  4. เข้าเว็บ → ตั้งค่าระบบ → ข้อมูลระบบ เพื่อใส่ชื่อ โลโก้ และสีประจำวิทยาลัย');
        $this->line('  5. ตั้งค่าระบบ → ปีการศึกษา แล้วนำเข้ารายชื่อนักศึกษา');

        return self::SUCCESS;
    }

    private function ensureAppKey(): bool
    {
        if (config('app.key')) {
            $this->components->info('APP_KEY มีอยู่แล้ว');

            return true;
        }

        if (! file_exists(base_path('.env'))) {
            $this->error('ไม่พบไฟล์ .env — คัดลอกจาก .env.production.example แล้วกรอกค่าก่อน');

            return false;
        }

        Artisan::call('key:generate', ['--force' => true]);
        $this->components->info('สร้าง APP_KEY ใหม่แล้ว');

        return true;
    }

    private function createFirstAdmin(): void
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            $this->components->info('มีบัญชีผู้ดูแลระบบอยู่แล้ว — ข้ามขั้นตอนนี้');

            return;
        }

        $name = $this->option('admin-name');
        $email = $this->option('admin-email');
        $password = $this->option('admin-password');

        if (! $email && $this->shouldAsk()) {
            $name = $this->ask('ชื่อผู้ดูแลระบบ', 'ผู้ดูแลระบบ');
            $email = $this->ask('อีเมลสำหรับเข้าสู่ระบบ');
            $password = $this->secret('รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)');
        }

        if (! $email || ! $password) {
            $this->components->warn('ยังไม่ได้สร้างบัญชีผู้ดูแลระบบ — สร้างภายหลังด้วย php artisan app:install อีกครั้ง');

            return;
        }

        $validator = Validator::make(
            ['name' => $name ?: 'ผู้ดูแลระบบ', 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return;
        }

        User::create([
            'name' => $name ?: 'ผู้ดูแลระบบ',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
        ]);

        $this->components->info("สร้างบัญชีผู้ดูแลระบบ {$email} แล้ว");
    }

    private function setCollegeName(): void
    {
        $college = $this->option('college');

        if (! $college && $this->shouldAsk()) {
            $college = $this->ask('ชื่อวิทยาลัย (เว้นว่างเพื่อตั้งภายหลังในหน้าเว็บ)');
        }

        if (! $college) {
            return;
        }

        SystemSetting::current()->update(['college_name' => $college]);
        SystemSetting::forgetCached();

        $this->components->info("ตั้งชื่อวิทยาลัยเป็น {$college}");
    }

    private function shouldAsk(): bool
    {
        return ! $this->option('no-interaction-safe') && $this->input->isInteractive();
    }
}
