<?php

use App\Models\Department;
use App\Support\DepartmentCardPhotoStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_card_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('role', 16);
            $table->string('mime', 80);
            $table->longText('payload');
            $table->timestamps();
            $table->unique(['department_id', 'role']);
        });

        if (Schema::hasTable('departments')) {
            Department::query()->orderBy('id')->each(function (Department $department) {
                DepartmentCardPhotoStore::ingestFromDisk($department);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_card_photos');
    }
};
