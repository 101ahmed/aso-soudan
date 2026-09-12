<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('counselor_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_staff_assignments');
    }
};
