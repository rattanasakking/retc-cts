<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usage of the public self-report form, so staff can see how many
     * graduates actually reach it and where they drop off — the career_statuses
     * table only records the ones who made it all the way to the end.
     *
     * Deliberately holds no IP address or user agent: this is an unauthenticated
     * public page, and a per-day salted hash is enough to count distinct
     * visitors without keeping anything that identifies one.
     */
    public function up(): void
    {
        Schema::create('self_report_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 30)->comment('visit | verify_failed | verify_success | submitted');
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_hash', 64)->comment('sha256(ip + user agent + date + app key) — rotates daily, not reversible');
            $table->boolean('is_mobile')->default(false);
            $table->timestamps();

            $table->index(['event', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_report_events');
    }
};
