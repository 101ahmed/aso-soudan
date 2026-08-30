<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_fr')->nullable();
            $table->string('type', 40)->default('association')->index();
            $table->string('city', 120)->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_fr')->nullable();
            $table->string('partnership_status', 30)->default('prospect')->index();
            $table->boolean('is_public')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->nullable()->constrained('external_partners')->nullOnDelete();
            $table->string('title_ar');
            $table->string('title_fr')->nullable();
            $table->string('category', 40)->default('other')->index();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('external_contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('applicant_name');
            $table->string('applicant_phone', 50)->nullable();
            $table->string('applicant_email')->nullable();
            $table->string('applicant_organization')->nullable();
            $table->foreignId('partner_id')->nullable()->constrained('external_partners')->nullOnDelete();
            $table->string('partner_name')->nullable();
            $table->text('reason');
            $table->string('status', 30)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_contact_requests');
        Schema::dropIfExists('external_documents');
        Schema::dropIfExists('external_partners');
    }
};
