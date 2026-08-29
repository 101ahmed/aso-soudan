<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('type', 30)->default('activity')->index()->after('id');
            $table->string('slug')->nullable()->unique()->after('title_fr');
            $table->string('location_ar')->nullable()->after('location');
            $table->string('location_fr')->nullable()->after('location_ar');
            $table->boolean('show_on_secretariat')->default(true)->after('status');
            $table->boolean('show_on_home')->default(false)->after('show_on_secretariat');
            $table->timestamp('published_at')->nullable()->index()->after('show_on_home');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'slug',
                'location_ar',
                'location_fr',
                'show_on_secretariat',
                'show_on_home',
                'published_at',
            ]);
        });
    }
};
