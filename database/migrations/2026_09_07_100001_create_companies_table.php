<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Local index of juristic persons (บริษัท / ห้างหุ้นส่วน) used to
     * autocomplete the workplace field.
     *
     * Held locally rather than queried live: the self-report form is public
     * and rate-limited, so proxying an external registry per keystroke would
     * both expose that registry to abuse through this site and leave students
     * staring at a stalled form whenever it is slow or down.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('juristic_id', 20)->nullable()->unique()
                ->comment('เลขทะเบียนนิติบุคคล 13 หลัก — null สำหรับชื่อที่เจ้าหน้าที่/นักศึกษากรอกเอง');
            $table->string('type', 100)->nullable()->comment('บริษัทจำกัด / ห้างหุ้นส่วนจำกัด ฯลฯ');
            $table->string('status', 100)->nullable()->comment('สถานะนิติบุคคล ตามที่ DBD ระบุ');
            $table->string('province', 100)->nullable()->index();
            $table->string('district', 100)->nullable();
            $table->string('source', 20)->default('dbd')->comment('dbd = นำเข้าจากชุดข้อมูลเปิด | entered = มีคนกรอกเข้ามาเอง');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
