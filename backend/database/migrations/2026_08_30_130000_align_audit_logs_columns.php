<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 80)->index();
                $table->string('target_type', 80)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['target_type', 'target_id']);
            });

            return;
        }

        $needsTargetType = ! Schema::hasColumn('audit_logs', 'target_type');
        $needsTargetId = ! Schema::hasColumn('audit_logs', 'target_id');
        $needsIp = ! Schema::hasColumn('audit_logs', 'ip');
        $needsMeta = ! Schema::hasColumn('audit_logs', 'meta');

        Schema::table('audit_logs', function (Blueprint $table) use ($needsTargetType, $needsTargetId, $needsIp, $needsMeta) {
            if ($needsTargetType) {
                $table->string('target_type', 80)->nullable()->after('action');
            }
            if ($needsTargetId) {
                $table->unsignedBigInteger('target_id')->nullable();
            }
            if ($needsIp) {
                $table->string('ip', 45)->nullable();
            }
            if ($needsMeta) {
                $table->json('meta')->nullable();
            }
        });

        if (Schema::hasColumn('audit_logs', 'auditable_type')) {
            DB::statement('UPDATE audit_logs SET target_type = auditable_type, target_id = auditable_id WHERE target_type IS NULL');
        }
        if (Schema::hasColumn('audit_logs', 'ip_address')) {
            DB::statement('UPDATE audit_logs SET ip = ip_address WHERE ip IS NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'target_type')) {
                $table->dropColumn(['target_type', 'target_id']);
            }
            if (Schema::hasColumn('audit_logs', 'ip')) {
                $table->dropColumn('ip');
            }
            if (Schema::hasColumn('audit_logs', 'meta')) {
                $table->dropColumn('meta');
            }
        });
    }
};
