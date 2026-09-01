<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Everything another college needs to make this install its own without
     * touching code: the short name shown in the sidebar and on a phone's
     * home screen, contact details for the public pages, and the brand colour.
     */
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('system_name')
                ->comment('shown where there is no room for the full name — sidebar, mobile header, PWA short_name');
            $table->string('primary_color', 7)->nullable()->after('logo_path')
                ->comment('hex brand colour overriding the theme default at runtime');
            $table->string('contact_email')->nullable()->after('primary_color');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['short_name', 'primary_color', 'contact_email', 'contact_phone']);
        });
    }
};
