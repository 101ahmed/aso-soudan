<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('council_members', function (Blueprint $table) {
            $table->id();
            $table->string('council_code', 64)->default('shura')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('photo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('position_code', 40)->default('member')->index();
            $table->string('position_ar')->nullable();
            $table->string('position_fr')->nullable();
            $table->text('bio_ar')->nullable();
            $table->text('bio_fr')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_public')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('council_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('council_code', 64)->default('shura')->index();
            $table->string('reference', 50)->nullable()->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('planned')->index();
            $table->longText('agenda_ar')->nullable();
            $table->longText('agenda_fr')->nullable();
            $table->longText('minutes_ar')->nullable();
            $table->longText('minutes_fr')->nullable();
            $table->string('visibility', 20)->default('internal')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('council_meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('council_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('council_member_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['council_meeting_id', 'council_member_id'], 'council_attendance_unique');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('show_on_home')->index();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
        Schema::dropIfExists('council_meeting_attendances');
        Schema::dropIfExists('council_meetings');
        Schema::dropIfExists('council_members');
    }
};
