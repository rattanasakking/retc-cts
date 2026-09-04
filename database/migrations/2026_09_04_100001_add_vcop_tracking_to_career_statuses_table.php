<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Staff re-key each reported career status into V-COP (ศูนย์กำลังคน
     * อาชีวศึกษา) by hand. Marking the row here is what stops the same
     * student being entered twice — or missed — when several people share
     * the work.
     *
     * Kept on career_statuses rather than on the student: a graduate who
     * reports a new status later produces a new row, which is correctly
     * unmarked again because that new answer still has to be re-keyed.
     */
    public function up(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->timestamp('vcop_recorded_at')->nullable()->after('notes');
            $table->foreignId('vcop_recorded_by')->nullable()->after('vcop_recorded_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('career_statuses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vcop_recorded_by');
            $table->dropColumn('vcop_recorded_at');
        });
    }
};
