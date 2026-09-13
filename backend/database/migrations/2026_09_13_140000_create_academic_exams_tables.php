<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('period', 20)->default('term1')->index();
            $table->date('exam_date');
            $table->decimal('max_score', 8, 2);
            $table->decimal('pass_score', 8, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['level_id', 'academic_year_id', 'period']);
        });

        Schema::create('academic_exam_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_exam_id')->constrained('academic_exams')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->boolean('is_absent')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_exam_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_exam_grades');
        Schema::dropIfExists('academic_exams');
    }
};
