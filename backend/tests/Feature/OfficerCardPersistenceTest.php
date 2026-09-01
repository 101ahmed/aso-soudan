<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentCardPhoto;
use App\Models\Role;
use App\Models\User;
use App\Support\DepartmentCardPhotoStore;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OfficerCardPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this PHP build.');
        }

        $this->seed(RolePermissionSeeder::class);
        $this->seed(DepartmentSeeder::class);
    }

    public function test_seeder_does_not_overwrite_existing_officer_or_deputy_cards(): void
    {
        $department = Department::query()->where('code', 'academic')->firstOrFail();
        $department->update([
            'officer_name_ar' => 'اسم محفوظ',
            'officer_name_fr' => 'Nom persisté',
            'officer_bio_fr' => 'Bio custom',
            'officer_photo_path' => 'db:officer',
            'deputy_name_ar' => 'نائب محفوظ',
            'deputy_name_fr' => 'Adjoint persisté',
            'deputy_photo_path' => 'db:deputy',
        ]);

        $this->seed(DepartmentSeeder::class);

        $department->refresh();
        $this->assertSame('Nom persisté', $department->officer_name_fr);
        $this->assertSame('اسم محفوظ', $department->officer_name_ar);
        $this->assertSame('Bio custom', $department->officer_bio_fr);
        $this->assertSame('db:officer', $department->officer_photo_path);
        $this->assertSame('Adjoint persisté', $department->deputy_name_fr);
        $this->assertSame('db:deputy', $department->deputy_photo_path);
    }

    public function test_officer_photo_survives_storage_disk_wipe(): void
    {
        $admin = $this->superAdmin();
        $photo = UploadedFile::fake()->createWithContent('amin.jpg', str_repeat('JPEGDATA', 64));

        $payload = $this->actingAs($admin)
            ->post('/api/admin/departments/academic/officer', [
                'officer_name_fr' => 'Ibrahim persisté',
                'officer_name_ar' => 'إبراهيم محفوظ',
                'officer_title_fr' => 'Secrétaire académique',
                'officer_is_public' => '1',
                'photo' => $photo,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertSame('Ibrahim persisté', $payload['officer']['name_fr']);
        $this->assertSame('db:officer', $payload['officer']['photo_path']);
        $this->assertStringContainsString('/api/public/departments/academic/officer-photo', $payload['officer']['photo_url']);

        $this->assertDatabaseHas('department_card_photos', [
            'department_id' => Department::query()->where('code', 'academic')->value('id'),
            'role' => 'officer',
            'mime' => 'image/jpeg',
        ]);

        $this->deleteTestPublicFiles();

        $afterWipe = $this->actingAs($admin)
            ->getJson('/api/admin/departments/academic')
            ->assertOk()
            ->json('data');

        $this->assertSame('Ibrahim persisté', $afterWipe['officer']['name_fr']);
        $this->assertNotEmpty($afterWipe['officer']['photo_url']);

        $this->get($afterWipe['officer']['photo_url'])
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->getJson('/api/public/departments')
            ->assertOk()
            ->assertJsonFragment(['name_fr' => 'Ibrahim persisté']);
    }

    public function test_ingests_legacy_disk_photo_then_survives_wipe(): void
    {
        $department = Department::query()->where('code', 'general')->firstOrFail();
        $relative = 'officers/general/legacy.jpg';
        $full = storage_path('app/public/'.$relative);
        if (! is_dir(dirname($full))) {
            mkdir(dirname($full), 0755, true);
        }
        file_put_contents($full, 'legacy-photo-bytes');
        $department->update(['officer_photo_path' => $relative]);

        $this->artisan('rdp:ingest-officer-photos')->assertSuccessful();

        $department->refresh();
        $this->assertSame('db:officer', $department->officer_photo_path);
        $this->assertTrue(
            DepartmentCardPhoto::query()
                ->where('department_id', $department->id)
                ->where('role', 'officer')
                ->exists()
        );

        @unlink($full);

        $contents = DepartmentCardPhotoStore::contents($department->fresh(), 'officer');
        $this->assertNotNull($contents);
        $this->assertSame('legacy-photo-bytes', $contents[0]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'SUPER_ADMIN')->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles');

        return $user;
    }

    private function deleteTestPublicFiles(): void
    {
        foreach ([
            storage_path('app/public/officers/general/legacy.jpg'),
        ] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
