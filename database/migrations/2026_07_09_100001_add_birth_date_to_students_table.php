<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('last_name')
                ->comment('ใช้ยืนยันตัวตนในแบบฟอร์มสาธารณะสำหรับนักศึกษา');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
