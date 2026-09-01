<?php

use App\Support\StoredFileStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('collection', 64)->index();
            $table->string('original_name')->nullable();
            $table->string('mime', 120);
            $table->unsignedInteger('size')->default(0);
            $table->longText('payload');
            $table->string('legacy_path')->nullable()->index();
            $table->timestamps();
        });

        StoredFileStore::ingestAll();
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
