<?php

namespace Tests\Unit;

use App\Support\MediaUrl;
use App\Support\StoredFileStore;
use Tests\TestCase;

class StoredFileStoreTest extends TestCase
{
    public function test_uuid_from_path_ignores_officer_db_markers_and_disk_paths(): void
    {
        $this->assertNull(StoredFileStore::uuidFromPath(null));
        $this->assertNull(StoredFileStore::uuidFromPath(''));
        $this->assertNull(StoredFileStore::uuidFromPath('db:officer'));
        $this->assertNull(StoredFileStore::uuidFromPath('db:deputy'));
        $this->assertNull(StoredFileStore::uuidFromPath('news/photo.jpg'));
        $this->assertFalse(StoredFileStore::isStored('db:officer'));

        $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $this->assertSame($uuid, StoredFileStore::uuidFromPath('db:'.$uuid));
        $this->assertTrue(StoredFileStore::isStored('db:'.$uuid));
    }

    public function test_media_url_serves_stored_files_from_api_not_storage(): void
    {
        $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $this->assertSame('/api/public/files/'.$uuid, MediaUrl::absolute('db:'.$uuid));
        $this->assertStringNotContainsString('/api/public/files/', (string) MediaUrl::absolute('db:officer'));
        $this->assertStringContainsString('news/legacy.jpg', (string) MediaUrl::absolute('news/legacy.jpg'));
    }
}
