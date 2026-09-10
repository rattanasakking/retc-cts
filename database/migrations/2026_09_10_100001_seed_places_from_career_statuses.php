<?php

use App\Support\CompanyImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Until now the places table only filled up when somebody pressed the
     * import button in Settings. A college that never did got a suggestion
     * list showing map results alone — every workplace already on file for
     * their own graduates was missing from it.
     *
     * Seeding here means every install gets the names it already holds the
     * moment it migrates, with no button to know about.
     */
    public function up(): void
    {
        if (! Schema::hasTable('career_statuses') || ! Schema::hasColumn('companies', 'kind')) {
            return;
        }

        (new CompanyImporter)->importFromExistingCareerStatuses();
    }

    public function down(): void
    {
        // Names copied in are indistinguishable from ones typed later, and
        // removing them would only make the list worse. Nothing to undo.
    }
};
