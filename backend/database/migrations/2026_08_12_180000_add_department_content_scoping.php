<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('author_id')->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('show_on_home')->default(false)->after('is_featured');
        });

        Schema::table('albums', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->string('status', 20)->default('draft')->after('cover_path')->index();
            $table->foreignId('created_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_fr');
            $table->longText('content_ar')->nullable();
            $table->longText('content_fr')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('show_on_secretariat')->default(true);
            $table->boolean('show_on_home')->default(false);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');

        Schema::table('albums', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn('status');
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'show_on_home']);
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('department_user');
    }
};
