<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('council_meetings') || ! Schema::hasColumn('council_meetings', 'map_url')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE council_meetings ALTER COLUMN map_url TYPE VARCHAR(2048)');
        } else {
            DB::statement('ALTER TABLE council_meetings MODIFY map_url VARCHAR(2048) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('council_meetings') || ! Schema::hasColumn('council_meetings', 'map_url')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            return;
        }
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE council_meetings ALTER COLUMN map_url TYPE VARCHAR(500)');
        } else {
            DB::statement('ALTER TABLE council_meetings MODIFY map_url VARCHAR(500) NULL');
        }
    }
};
