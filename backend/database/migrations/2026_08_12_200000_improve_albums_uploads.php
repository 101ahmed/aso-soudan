<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title_fr');
            $table->boolean('show_on_home')->default(false)->after('is_published');
            $table->boolean('show_on_gallery')->default(true)->after('show_on_home');
        });
    }

    public function down(): void
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn(['slug', 'show_on_home', 'show_on_gallery']);
        });
    }
};
