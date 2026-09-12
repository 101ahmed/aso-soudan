<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_revenues', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->nullable()
                ->after('recorded_by')
                ->constrained('members')
                ->nullOnDelete();
            $table->unique('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('finance_revenues', function (Blueprint $table) {
            $table->dropUnique(['member_id']);
            $table->dropConstrainedForeignId('member_id');
        });
    }
};
