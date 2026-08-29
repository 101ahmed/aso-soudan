<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('last_name');
            $table->string('gender', 20)->nullable()->index()->after('birth_date');
            $table->index('city');
            $table->index('membership_type');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['membership_type']);
            $table->dropColumn(['birth_date', 'gender']);
        });
    }
};
