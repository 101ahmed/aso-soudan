<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'level_id']);
        });

        $now = now();
        $pairs = DB::table('class_groups')
            ->whereNotNull('teacher_id')
            ->whereNotNull('level_id')
            ->whereNull('deleted_at')
            ->select('teacher_id', 'level_id')
            ->distinct()
            ->get();

        foreach ($pairs as $pair) {
            DB::table('teacher_level')->insertOrIgnore([
                'teacher_id' => $pair->teacher_id,
                'level_id' => $pair->level_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_level');
    }
};
