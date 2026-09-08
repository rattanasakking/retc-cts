<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A copy of the name with the juristic wrapper stripped — "บริษัท ซีพี
     * ออลล์ จำกัด (มหาชน)" becomes "ซีพี ออลล์".
     *
     * Without it, someone typing the name they actually know ("ซีพี") matches
     * nothing, because every row in the table starts with the word "บริษัท".
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('search_name')->nullable()->after('name')->index();
        });

        Company::query()->orderBy('id')->chunkById(500, function ($companies) {
            foreach ($companies as $company) {
                $company->newQuery()
                    ->whereKey($company->getKey())
                    ->update(['search_name' => Company::normalise($company->name)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('search_name');
        });
    }
};
