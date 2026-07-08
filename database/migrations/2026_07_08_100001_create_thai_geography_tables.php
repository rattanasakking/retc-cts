<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thai_provinces', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name_th');
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
        });

        Schema::create('thai_districts', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name_th');
            $table->unsignedInteger('province_id');
            $table->foreign('province_id')->references('id')->on('thai_provinces')->restrictOnDelete();
            $table->index('province_id');
        });

        Schema::create('thai_subdistricts', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name_th');
            $table->unsignedInteger('district_id');
            $table->foreign('district_id')->references('id')->on('thai_districts')->restrictOnDelete();
            $table->index('district_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thai_subdistricts');
        Schema::dropIfExists('thai_districts');
        Schema::dropIfExists('thai_provinces');
    }
};
