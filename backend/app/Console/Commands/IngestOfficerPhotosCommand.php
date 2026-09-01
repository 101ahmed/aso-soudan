<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Support\DepartmentCardPhotoStore;
use Illuminate\Console\Command;

class IngestOfficerPhotosCommand extends Command
{
    protected $signature = 'rdp:ingest-officer-photos';

    protected $description = 'Copy officer/deputy card photos from ephemeral disk into the database';

    public function handle(): int
    {
        $copied = 0;

        Department::query()->orderBy('id')->each(function (Department $department) use (&$copied) {
            $copied += DepartmentCardPhotoStore::ingestFromDisk($department);
        });

        $this->info("Ingested {$copied} officer/deputy photo(s) into the database.");

        return self::SUCCESS;
    }
}
