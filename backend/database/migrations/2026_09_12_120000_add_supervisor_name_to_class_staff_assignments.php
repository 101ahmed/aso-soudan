<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_staff_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('class_staff_assignments', 'supervisor_name')) {
                $table->string('supervisor_name', 150)->nullable();
            }
            if (! Schema::hasColumn('class_staff_assignments', 'counselor_name')) {
                $table->string('counselor_name', 150)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_staff_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('class_staff_assignments', 'supervisor_name')) {
                $table->dropColumn('supervisor_name');
            }
        });
    }
};
