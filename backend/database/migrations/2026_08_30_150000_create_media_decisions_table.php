<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('kind', 20)->default('decision')->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('details_ar')->nullable();
            $table->text('details_fr')->nullable();
            $table->string('responsible_ar');
            $table->string('responsible_fr');
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('decided_on')->index();
            $table->date('due_on')->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_decisions');
    }
};
