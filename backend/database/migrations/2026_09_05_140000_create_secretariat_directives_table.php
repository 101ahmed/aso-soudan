<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secretariat_directives', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 50)->nullable()->index();
            $table->uuid('broadcast_id')->nullable()->index();
            $table->boolean('is_broadcast')->default(false);
            $table->foreignId('sender_department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('recipient_department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('classification', 20)->default('info')->index();
            $table->string('status', 20)->default('sent')->index();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secretariat_directives');
    }
};
