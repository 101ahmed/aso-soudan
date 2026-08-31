<?php

use App\Support\FrenchAdultCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        FrenchAdultCatalog::ensure();
    }

    public function down(): void
    {
        // Catalog rows are kept: production may already have enrollments.
    }
};
