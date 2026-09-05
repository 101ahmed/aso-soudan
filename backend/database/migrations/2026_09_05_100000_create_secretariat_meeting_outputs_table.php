<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secretariat_meeting_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->date('meeting_on')->index();
            $table->string('location')->nullable();
            $table->text('attendees_ar')->nullable();
            $table->text('attendees_fr')->nullable();
            $table->text('agenda_ar')->nullable();
            $table->text('agenda_fr')->nullable();
            $table->text('outputs_ar')->nullable();
            $table->text('outputs_fr')->nullable();
            $table->text('follow_up_ar')->nullable();
            $table->text('follow_up_fr')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretariat_meeting_outputs');
    }
};
