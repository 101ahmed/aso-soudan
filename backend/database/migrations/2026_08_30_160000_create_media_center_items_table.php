<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_center_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('kind', 40)->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('content_ar')->nullable();
            $table->text('content_fr')->nullable();
            $table->string('source_ar')->nullable();
            $table->string('source_fr')->nullable();
            $table->string('person_ar')->nullable();
            $table->string('person_fr')->nullable();
            $table->string('location_ar')->nullable();
            $table->string('location_fr')->nullable();
            $table->string('external_url')->nullable();
            $table->string('image_path')->nullable();
            $table->date('occurred_on')->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_center_items');
    }
};
