<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presidential_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->index();
            $table->string('decision_number', 50)->nullable()->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('classification', 20)->default('info')->index();
            $table->string('status', 20)->default('upcoming')->index();
            $table->text('agenda_ar')->nullable();
            $table->text('agenda_fr')->nullable();
            $table->text('minutes_ar')->nullable();
            $table->text('minutes_fr')->nullable();
            $table->text('decisions_ar')->nullable();
            $table->text('decisions_fr')->nullable();
            $table->text('follow_up_ar')->nullable();
            $table->text('follow_up_fr')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presidential_directives', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->index();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('classification', 20)->default('info')->index();
            $table->string('status', 20)->default('sent')->index();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('presidential_archive_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 40)->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('body_ar')->nullable();
            $table->text('body_fr')->nullable();
            $table->string('decision_number', 50)->nullable()->index();
            $table->date('document_date')->nullable()->index();
            $table->string('source_type', 40)->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presidential_archive_items');
        Schema::dropIfExists('presidential_directives');
        Schema::dropIfExists('presidential_meetings');
    }
};
