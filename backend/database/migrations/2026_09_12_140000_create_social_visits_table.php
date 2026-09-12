<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_visits', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 50)->nullable();
            $table->string('place');
            $table->string('visit_type', 40)->index();
            $table->text('reason');
            $table->date('visited_on')->index();
            $table->time('visited_at')->nullable();
            $table->string('visitors')->nullable();
            $table->string('status', 20)->default('planned')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_visits');
    }
};
