<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('officer_name_ar')->nullable()->after('sort_order');
            $table->string('officer_name_fr')->nullable()->after('officer_name_ar');
            $table->string('officer_title_ar')->nullable()->after('officer_name_fr');
            $table->string('officer_title_fr')->nullable()->after('officer_title_ar');
            $table->text('officer_bio_ar')->nullable()->after('officer_title_fr');
            $table->text('officer_bio_fr')->nullable()->after('officer_bio_ar');
            $table->string('officer_email')->nullable()->after('officer_bio_fr');
            $table->string('officer_phone', 50)->nullable()->after('officer_email');
            $table->string('officer_photo_path')->nullable()->after('officer_phone');
            $table->boolean('officer_is_public')->default(true)->after('officer_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
                'officer_name_ar',
                'officer_name_fr',
                'officer_title_ar',
                'officer_title_fr',
                'officer_bio_ar',
                'officer_bio_fr',
                'officer_email',
                'officer_phone',
                'officer_photo_path',
                'officer_is_public',
            ]);
        });
    }
};
