<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('council_meetings') && ! Schema::hasColumn('council_meetings', 'map_url')) {
            Schema::table('council_meetings', function (Blueprint $table) {
                $table->string('map_url', 2048)->nullable()->after('location');
            });
        }

        Schema::create('parent_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 50);
            $table->string('city', 120)->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_registration_children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_registration_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('level', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('parent_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('details_ar')->nullable();
            $table->text('details_fr')->nullable();
            $table->string('form_url', 500)->nullable();
            $table->json('questions')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parent_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_survey_id')->constrained()->cascadeOnDelete();
            $table->string('parent_name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_survey_responses');
        Schema::dropIfExists('parent_surveys');
        Schema::dropIfExists('parent_registration_children');
        Schema::dropIfExists('parent_registrations');

        if (Schema::hasTable('council_meetings') && Schema::hasColumn('council_meetings', 'map_url')) {
            Schema::table('council_meetings', function (Blueprint $table) {
                $table->dropColumn('map_url');
            });
        }
    }
};
