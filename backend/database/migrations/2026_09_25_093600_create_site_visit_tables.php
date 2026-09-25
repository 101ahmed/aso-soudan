<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_hash', 64)->unique();
            $table->unsignedInteger('page_views')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
        });

        Schema::create('site_visit_days', function (Blueprint $table) {
            $table->id();
            $table->date('visited_on')->unique();
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('page_views')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visit_days');
        Schema::dropIfExists('site_visitors');
    }
};
