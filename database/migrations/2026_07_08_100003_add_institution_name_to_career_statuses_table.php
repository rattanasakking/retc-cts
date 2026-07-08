<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->string('institution_name')->nullable()->after('work_subdistrict_id')
                ->comment('ชื่อสถานศึกษาต่อ ใช้เมื่อ status = further_study');
        });
    }

    public function down(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->dropColumn('institution_name');
        });
    }
};
