<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('women_members', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('gender', 20)->default('female')->index();
            $table->string('residence')->nullable();
            $table->string('marital_status', 30)->nullable()->index();
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('women_members');
    }
};
