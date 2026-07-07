<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            // Filtered on every Dashboard/CareerStatusForm/ExportCenter/SendReminders
            // page load via where('is_active', true).
            $table->index('is_active');
        });

        Schema::table('students', function (Blueprint $table) {
            // Both filtered directly (Dashboard/ExportCenter/SendReminders program
            // and degree_level filters) and driven through DISTINCT+ORDER BY to
            // populate filter dropdowns.
            $table->index('program');
            $table->index('degree_level');
        });

        Schema::table('career_statuses', function (Blueprint $table) {
            // The Dashboard's stat cards and SendReminders' non-responder query
            // both filter by (academic_year_id, is_current) together — the
            // existing (academic_year_id, status) and (student_id, is_current)
            // composites don't cover this pair.
            $table->index(['academic_year_id', 'is_current']);

            // CareerStatusForm::save() supersedes the prior record for a
            // (student_id, academic_year_id) pair before inserting the new one.
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['program']);
            $table->dropIndex(['degree_level']);
        });

        Schema::table('career_statuses', function (Blueprint $table) {
            $table->dropIndex(['academic_year_id', 'is_current']);
            $table->dropIndex(['student_id', 'academic_year_id']);
        });
    }
};
