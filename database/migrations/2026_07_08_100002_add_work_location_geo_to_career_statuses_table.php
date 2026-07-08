<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->unsignedInteger('work_province_id')->nullable()->after('work_location');
            $table->unsignedInteger('work_district_id')->nullable()->after('work_province_id');
            $table->unsignedInteger('work_subdistrict_id')->nullable()->after('work_district_id');

            $table->foreign('work_province_id')->references('id')->on('thai_provinces')->nullOnDelete();
            $table->foreign('work_district_id')->references('id')->on('thai_districts')->nullOnDelete();
            $table->foreign('work_subdistrict_id')->references('id')->on('thai_subdistricts')->nullOnDelete();

            $table->index(['work_province_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->dropForeign(['work_province_id']);
            $table->dropForeign(['work_district_id']);
            $table->dropForeign(['work_subdistrict_id']);
            $table->dropColumn(['work_province_id', 'work_district_id', 'work_subdistrict_id']);
        });
    }
};
