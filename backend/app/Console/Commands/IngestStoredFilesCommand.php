<?php

namespace App\Console\Commands;

use App\Support\StoredFileStore;
use Illuminate\Console\Command;

class IngestStoredFilesCommand extends Command
{
    protected $signature = 'rdp:ingest-stored-files';

    protected $description = 'Copy leftover public-disk uploads (news, albums, events, …) into Postgres';

    public function handle(): int
    {
        $copied = StoredFileStore::ingestAll();
        $this->info("Ingested {$copied} stored file(s) into the database.");

        return self::SUCCESS;
    }
}
