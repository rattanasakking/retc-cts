<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('student_code')->unique();
            $table->string('national_id', 13)->nullable()->unique();
            $table->string('prefix')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('program')->nullable()->comment('สาขาวิชา');
            $table->string('degree_level')->nullable()->comment('ปวช./ปวส./ป.ตรี');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('graduated_at')->nullable();
            $table->string('status')->default('studying')->comment('studying, graduated, dropped_out');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['academic_year_id', 'status']);
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
