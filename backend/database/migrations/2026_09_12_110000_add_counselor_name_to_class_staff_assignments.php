<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_staff_assignments', function (Blueprint $table) {
            $table->string('counselor_name', 150)->nullable()->after('counselor_teacher_id');
            $table->string('supervisor_name', 150)->nullable()->after('supervisor_teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('class_staff_assignments', function (Blueprint $table) {
            $table->dropColumn(['counselor_name', 'supervisor_name']);
        });
    }
};
