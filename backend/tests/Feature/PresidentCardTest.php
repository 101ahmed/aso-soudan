<?php

namespace Tests\Feature;

use App\Models\PresidentProfile;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\StoredFileStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PresidentCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this PHP build.');
        }

        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_president_can_save_name_and_photo_and_public_page_reads_them(): void
    {
        $admin = $this->superAdmin();
        $payload = $this->actingAs($admin)
            ->post('/api/admin/president/card', [
                'name_ar' => 'محمد أحمد',
                'name_fr' => 'Mohamed Ahmed',
                'is_public' => '1',
                'photo' => UploadedFile::fake()->createWithContent('president.jpg', $this->tinyJpeg()),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertSame('محمد أحمد', $payload['name_ar']);
        $this->assertSame('Mohamed Ahmed', $payload['name_fr']);
        $this->assertTrue($payload['is_public']);
        $this->assertTrue(StoredFileStore::isStored($payload['photo_path']));
        $this->assertStringContainsString('/api/public/files/', $payload['photo_url']);

        $this->wipePublicStorage();

        $uuid = StoredFileStore::uuidFromPath($payload['photo_path']);
        $this->assertTrue(StoredFile::query()->where('uuid', $uuid)->exists());
        $this->get('/api/public/files/'.$uuid)->assertOk();

        $public = $this->getJson('/api/public/president')->assertOk()->json('data');
        $this->assertSame('محمد أحمد', $public['president']['name_ar']);
        $this->assertStringContainsString('/api/public/files/', $public['president']['photo_url']);
        $this->assertArrayNotHasKey('photo_path', $public['president']);

        $this->actingAs($admin)
            ->post('/api/admin/president/card', [
                'is_public' => '0',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $hidden = $this->getJson('/api/public/president')->assertOk()->json('data');
        $this->assertNull($hidden['president']['name_ar']);
        $this->assertNull($hidden['president']['photo_url']);
    }

    public function test_vice_president_card_is_separate_from_president(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->post('/api/admin/president/card', [
                'name_ar' => 'الرئيس',
                'name_fr' => 'President',
                'is_public' => '1',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $vp = $this->actingAs($admin)
            ->post('/api/admin/vice-president/card', [
                'name_ar' => 'نائب الرئيس',
                'name_fr' => 'Vice-president',
                'is_public' => '1',
                'photo' => UploadedFile::fake()->createWithContent('vp.jpg', $this->tinyJpeg()),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertSame('vice_president', $vp['office']);
        $this->assertSame('نائب الرئيس', $vp['name_ar']);
        $this->assertTrue(StoredFileStore::isStored($vp['photo_path']));

        $public = $this->getJson('/api/public/president')->assertOk()->json('data');
        $this->assertSame('الرئيس', $public['president']['name_ar']);
        $this->assertSame('نائب الرئيس', $public['vice_president']['name_ar']);
    }

    public function test_overview_includes_card(): void
    {
        PresidentProfile::query()->create([
            'office' => PresidentProfile::OFFICE_PRESIDENT,
            'name_ar' => 'رئيس الاختبار',
            'name_fr' => 'Président test',
            'is_public' => true,
        ]);

        $overview = $this->actingAs($this->superAdmin())
            ->getJson('/api/admin/president/overview')
            ->assertOk()
            ->json();

        $this->assertSame('رئيس الاختبار', $overview['card']['name_ar']);
    }

    private function tinyJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEA8QEA8PDw8PDw8PDw8PDw8PDw8PFhEWFhURFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMtNygtLisBCgoKDg0OGxAQGy0lHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAoACgMBIgACEQEDEQH/xAAbAAACAwEBAQAAAAAAAAAAAAAEBQIDBgABB//EABUQAQEAAAAAAAAAAAAAAAAAAAAB/8QAGAEAAwEBAAAAAAAAAAAAAAAAAAECAwT/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAACxgZ//xAAaEAACAwEBAAAAAAAAAAAAAAABAgADEQQT/9oACAEBAAE/AM1ZqJqJqJqP/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwB//9k=') ?: '';
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
            $path = $file->getPathname();
            $file->isDir() ? @rmdir($path) : @unlink($path);
        }
    }
}
