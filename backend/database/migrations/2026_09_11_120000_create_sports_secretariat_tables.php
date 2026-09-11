<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('age_category', 30)->index();
            $table->string('sport', 40)->default('football');
            $table->string('coach_ar')->nullable();
            $table->string('coach_fr')->nullable();
            $table->string('manager_ar')->nullable();
            $table->string('manager_fr')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('ranking')->nullable();
            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('wins')->default(0);
            $table->unsignedSmallInteger('draws')->default(0);
            $table->unsignedSmallInteger('losses')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->text('notes_ar')->nullable();
            $table->text('notes_fr')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->nullable()->constrained('sports_teams')->nullOnDelete();
            $table->boolean('is_national')->default(false)->index();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('position', 80)->nullable();
            $table->unsignedTinyInteger('number')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('appearances')->default(0);
            $table->unsignedSmallInteger('goals')->default(0);
            $table->unsignedSmallInteger('assists')->default(0);
            $table->unsignedSmallInteger('yellow_cards')->default(0);
            $table->unsignedSmallInteger('red_cards')->default(0);
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->nullable()->constrained('sports_teams')->nullOnDelete();
            $table->boolean('is_national')->default(false)->index();
            $table->string('kind', 30)->default('technical')->index();
            $table->string('name_ar');
            $table->string('name_fr');
            $table->string('role_ar')->nullable();
            $table->string('role_fr')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->nullable()->constrained('sports_teams')->nullOnDelete();
            $table->boolean('is_national')->default(false)->index();
            $table->string('competition_ar')->nullable();
            $table->string('competition_fr')->nullable();
            $table->string('opponent_ar');
            $table->string('opponent_fr');
            $table->date('played_on')->nullable()->index();
            $table->string('venue')->nullable();
            $table->boolean('is_home')->default(true);
            $table->unsignedTinyInteger('goals_for')->nullable();
            $table->unsignedTinyInteger('goals_against')->nullable();
            $table->string('status', 20)->default('played')->index();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->constrained('sports_teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('starts_at', 8)->nullable();
            $table->string('ends_at', 8)->nullable();
            $table->string('location')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('notes_fr')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sports_team_id')->nullable()->constrained('sports_teams')->nullOnDelete();
            $table->boolean('is_national')->default(false)->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->string('season', 20)->nullable();
            $table->string('location')->nullable();
            $table->string('ranking')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('notes_fr')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_camps', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->date('starts_on')->nullable()->index();
            $table->date('ends_on')->nullable();
            $table->string('location')->nullable();
            $table->text('notes_ar')->nullable();
            $table->text('notes_fr')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sports_join_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->nullable()->index();
            $table->string('full_name');
            $table->date('birth_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('city')->nullable();
            $table->string('age_category', 30)->nullable();
            $table->string('position', 80)->nullable();
            $table->foreignId('sports_team_id')->nullable()->constrained('sports_teams')->nullOnDelete();
            $table->boolean('for_national')->default(false);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports_join_requests');
        Schema::dropIfExists('sports_camps');
        Schema::dropIfExists('sports_tournaments');
        Schema::dropIfExists('sports_trainings');
        Schema::dropIfExists('sports_matches');
        Schema::dropIfExists('sports_staff');
        Schema::dropIfExists('sports_players');
        Schema::dropIfExists('sports_teams');
    }
};
