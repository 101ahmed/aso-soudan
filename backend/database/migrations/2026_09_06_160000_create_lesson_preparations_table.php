<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
            $table->date('lesson_date');
            $table->string('title');
            $table->string('unit')->nullable();
            $table->text('objectives')->nullable();
            $table->text('skills')->nullable();
            $table->text('concepts')->nullable();
            $table->text('intro')->nullable();
            $table->text('explanation')->nullable();
            $table->text('activities')->nullable();
            $table->text('group_work')->nullable();
            $table->text('assessment')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['teacher_id', 'lesson_date']);
            $table->index(['created_by_user_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_preparations');
    }
};
