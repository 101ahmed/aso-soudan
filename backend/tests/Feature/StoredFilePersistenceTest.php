<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\MediaUrl;
use App\Support\StoredFileStore;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoredFilePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this PHP build.');
        }

        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DepartmentSeeder::class);
    }

    public function test_put_bytes_survives_public_disk_wipe(): void
    {
        $stored = StoredFileStore::putBytes('JPEGDATA-NEWS', 'news', 'image/jpeg', 'cover.jpg');
        $this->assertTrue(StoredFileStore::isStored($stored['path']));
        $this->assertDatabaseCount('stored_files', 1);

        $this->wipePublicStorage();

        $uuid = StoredFileStore::uuidFromPath($stored['path']);
        $this->assertNotNull($uuid);

        $this->get('/api/public/files/'.$uuid)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertSee('JPEGDATA-NEWS', false);
    }

    public function test_news_image_upload_streams_after_storage_wipe(): void
    {
        $admin = $this->superAdmin();
        $photo = UploadedFile::fake()->createWithContent('news.jpg', str_repeat('JPEGDATA', 64));

        $payload = $this->actingAs($admin)
            ->post('/api/admin/departments/media/news', [
                'title_ar' => 'خبر محفوظ',
                'title_fr' => 'Actu persistée',
                'content_fr' => 'Le fichier vit dans Postgres.',
                'status' => 'published',
                'image' => $photo,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');

        $this->assertTrue(str_starts_with((string) $payload['image_path'], 'db:'));
        $this->assertStringContainsString('/api/public/files/', (string) $payload['image_url']);
        $this->assertDatabaseCount('stored_files', 1);

        $this->wipePublicStorage();

        $this->get($payload['image_url'])
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->getJson('/api/public/news')
            ->assertOk()
            ->assertJsonFragment(['title_fr' => 'Actu persistée']);
    }

    public function test_album_cover_and_media_stream_after_storage_wipe(): void
    {
        $admin = $this->superAdmin();
        $cover = UploadedFile::fake()->createWithContent('cover.jpg', 'COVERBYTES');

        $album = $this->actingAs($admin)
            ->post('/api/admin/departments/media/albums', [
                'title_ar' => 'ألبوم محفوظ',
                'title_fr' => 'Album persisté',
                'cover' => $cover,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');

        $this->assertTrue(str_starts_with((string) $album['cover_path'], 'db:'));
        $this->assertStringContainsString('/api/public/files/', (string) $album['cover_url']);

        $shot = UploadedFile::fake()->createWithContent('shot.jpg', 'MEDIABYTES');
        $mediaPayload = $this->actingAs($admin)
            ->post('/api/admin/departments/media/albums/'.$album['id'].'/media', [
                'image' => $shot,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json();

        $this->assertNotEmpty($mediaPayload['data'][0]['url'] ?? null);
        $this->assertStringContainsString('/api/public/files/', $mediaPayload['data'][0]['url']);

        $this->wipePublicStorage();

        $this->get($album['cover_url'])
            ->assertOk()
            ->assertSee('COVERBYTES', false);

        $this->get($mediaPayload['data'][0]['url'])
            ->assertOk()
            ->assertSee('MEDIABYTES', false);
    }

    public function test_ingests_legacy_disk_news_photo_then_survives_wipe(): void
    {
        $admin = $this->superAdmin();
        $relative = 'news/legacy-cover.jpg';
        $full = storage_path('app/public/'.$relative);
        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0755, true);
        }
        file_put_contents($full, 'legacy-news-bytes');

        $news = News::query()->create([
            'title_ar' => 'خبر قديم',
            'title_fr' => 'Ancienne actu',
            'slug' => 'ancienne-actu-test',
            'image_path' => $relative,
            'author_id' => $admin->id,
            'department_id' => \App\Models\Department::query()->where('code', 'media')->value('id'),
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->artisan('rdp:ingest-stored-files')->assertSuccessful();

        $news->refresh();
        $this->assertTrue(StoredFileStore::isStored($news->image_path));
        $this->assertTrue(
            StoredFile::query()->where('legacy_path', $relative)->exists()
        );

        @unlink($full);

        $this->get(MediaUrl::absolute($news->image_path))
            ->assertOk()
            ->assertSee('legacy-news-bytes', false);
    }

    public function test_event_image_upload_streams_after_storage_wipe(): void
    {
        $admin = $this->superAdmin();
        $photo = UploadedFile::fake()->createWithContent('event.jpg', 'EVENTBYTES');

        $payload = $this->actingAs($admin)
            ->post('/api/admin/departments/media/events', [
                'type' => 'activity',
                'title_ar' => 'فعالية محفوظة',
                'title_fr' => 'Événement persisté',
                'status' => 'published',
                'image' => $photo,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');

        $this->assertTrue(str_starts_with((string) $payload['image_path'], 'db:'));

        $this->wipePublicStorage();

        $this->get($payload['image_url'])
            ->assertOk()
            ->assertSee('EVENTBYTES', false);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'SUPER_ADMIN')->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles');

        return $user;
    }

    private function wipePublicStorage(): void
    {
        $root = storage_path('app/public');
        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
    }
}
