<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('help_type', 40)->index();
            $table->text('details');
            $table->unsignedTinyInteger('family_size')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_requests');
    }
};
