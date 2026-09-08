<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The table indexes places, not only companies: a graduate who goes on to
     * study needs the same lookup for their institution. Keeping both in one
     * table means one importer, one matcher and one suggestion list — `kind`
     * is what keeps a college from being offered as a workplace.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('kind', 20)->default('company')->after('name')
                ->comment('company = สถานประกอบการ | institution = สถานศึกษา');

            $table->index(['kind', 'search_name']);
        });

        // Names already recorded as places of further study belong to the
        // other kind.
        $institutions = DB::table('career_statuses')
            ->whereNotNull('institution_name')
            ->where('institution_name', '!=', '')
            ->distinct()
            ->pluck('institution_name')
            ->all();

        if ($institutions !== []) {
            DB::table('companies')->whereIn('name', $institutions)->update(['kind' => 'institution']);
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['kind', 'search_name']);
            $table->dropColumn('kind');
        });
    }
};
