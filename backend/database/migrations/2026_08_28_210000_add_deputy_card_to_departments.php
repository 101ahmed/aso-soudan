<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->string('deputy_name_ar')->nullable()->after('officer_is_public');
            $table->string('deputy_name_fr')->nullable()->after('deputy_name_ar');
            $table->string('deputy_title_ar')->nullable()->after('deputy_name_fr');
            $table->string('deputy_title_fr')->nullable()->after('deputy_title_ar');
            $table->text('deputy_bio_ar')->nullable()->after('deputy_title_fr');
            $table->text('deputy_bio_fr')->nullable()->after('deputy_bio_ar');
            $table->string('deputy_email')->nullable()->after('deputy_bio_fr');
            $table->string('deputy_phone', 50)->nullable()->after('deputy_email');
            $table->string('deputy_photo_path')->nullable()->after('deputy_phone');
            $table->boolean('deputy_is_public')->default(true)->after('deputy_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
                'deputy_name_ar',
                'deputy_name_fr',
                'deputy_title_ar',
                'deputy_title_fr',
                'deputy_bio_ar',
                'deputy_bio_fr',
                'deputy_email',
                'deputy_phone',
                'deputy_photo_path',
                'deputy_is_public',
            ]);
        });
    }
};
