<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_revenues', function (Blueprint $table) {
            $table->id();
            $table->date('occurred_on')->index();
            $table->decimal('amount', 12, 2);
            $table->string('source', 40)->index();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('occurred_on')->index();
            $table->decimal('amount', 12, 2);
            $table->string('category', 40)->index();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('project_ar')->nullable();
            $table->string('project_fr')->nullable();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
        Schema::dropIfExists('finance_revenues');
        Schema::dropIfExists('finance_budgets');
    }
};
