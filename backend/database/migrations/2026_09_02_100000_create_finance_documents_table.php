<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 40)->unique();
            $table->string('title_ar');
            $table->string('title_fr')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_fr')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('finance_documents')->insert([
            [
                'kind' => 'general_report',
                'title_ar' => 'تقرير مالي عام',
                'title_fr' => 'Rapport financier public',
                'body_ar' => null,
                'body_fr' => null,
                'file_path' => null,
                'original_name' => null,
                'mime' => null,
                'size' => null,
                'is_published' => false,
                'published_at' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kind' => 'subscriptions_announcement',
                'title_ar' => 'إعلان الاشتراكات',
                'title_fr' => 'Annonce des cotisations',
                'body_ar' => null,
                'body_fr' => null,
                'file_path' => null,
                'original_name' => null,
                'mime' => null,
                'size' => null,
                'is_published' => false,
                'published_at' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_documents');
    }
};
