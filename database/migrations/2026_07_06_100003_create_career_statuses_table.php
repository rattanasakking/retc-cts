<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_statuses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->restrictOnDelete()
                ->comment('ปีการศึกษาที่สำรวจ/ติดตามภาวะการมีงานทำ');

            $table->string('status')->comment('employed, unemployed, further_study, military_service, entrepreneur, other');
            $table->string('company_name')->nullable();
            $table->string('position')->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->string('employment_type')->nullable()->comment('full_time, part_time, freelance, contract');
            $table->string('work_location')->nullable();
            $table->boolean('is_related_to_major')->nullable()->comment('ทำงานตรงสาขาที่เรียนหรือไม่');
            $table->date('effective_date');
            $table->string('source')->default('manual')->comment('manual, imported, survey');
            $table->boolean('is_current')->default(true);

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'is_current']);
            $table->index(['academic_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_statuses');
    }
};
