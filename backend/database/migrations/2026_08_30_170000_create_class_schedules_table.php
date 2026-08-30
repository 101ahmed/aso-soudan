<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_group_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();

            $table->unique(['class_group_id', 'weekday', 'starts_at'], 'class_schedule_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
