<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('president_profiles', function (Blueprint $table) {
            $table->string('office', 32)->default('president')->after('id');
        });

        DB::table('president_profiles')->where(function ($query) {
            $query->whereNull('office')->orWhere('office', '');
        })->update(['office' => 'president']);

        Schema::table('president_profiles', function (Blueprint $table) {
            $table->unique('office');
        });
    }

    public function down(): void
    {
        Schema::table('president_profiles', function (Blueprint $table) {
            $table->dropUnique(['office']);
            $table->dropColumn('office');
        });
    }
};
