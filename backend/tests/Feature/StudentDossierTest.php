<?php

namespace Tests\Feature;

use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\Student;
use App\Models\User;
use App\Support\StoredFileStore;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentDossierTest extends TestCase
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

    public function test_admin_can_store_photo_notes_and_age_then_download_pdf(): void
    {
        $admin = $this->superAdmin();
        [$stage, $level] = $this->stageAndLevel();
        $birth = now()->subYears(11)->subMonths(2)->toDateString();
        $png = $this->tinyJpeg();

        $created = $this->actingAs($admin)
            ->post('/api/admin/academic/students', [
                'first_name' => 'فاطمة',
                'last_name' => 'محمد',
                'birth_date' => $birth,
                'gender' => 'female',
                'education_stage_id' => $stage->id,
                'level_id' => $level->id,
                'status' => 'active',
                'notes' => 'ملاحظات المرشد: مجتهدة في الصف.',
                'photo' => UploadedFile::fake()->createWithContent('student.jpg', $png),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');

        $this->assertSame(11, $created['age']);
        $this->assertSame('ملاحظات المرشد: مجتهدة في الصف.', $created['notes']);
        $this->assertTrue(str_starts_with((string) $created['photo_path'], 'db:'));
        $this->assertStringContainsString('/api/public/files/', (string) $created['photo_url']);
        $this->assertDatabaseCount('stored_files', 1);

        $uuid = StoredFileStore::uuidFromPath($created['photo_path']);
        $this->assertNotNull($uuid);
        $this->get('/api/public/files/'.$uuid)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $html = view('reports.student-dossier', [
            'locale' => 'ar',
            'student' => Student::query()->with(['level', 'educationStage', 'academicYear', 'subjects'])->findOrFail($created['id']),
            'photoSrc' => 'data:image/jpeg;base64,'.base64_encode($png),
        ])->render();
        $this->assertStringContainsString('الرابطة السودانية برين', $html);
        $this->assertStringContainsString('الأمانة الأكاديمية', $html);
        $this->assertStringContainsString('ملف الطالب', $html);
        $this->assertStringContainsString('فاطمة محمد', $html);
        $this->assertStringContainsString('ملاحظات مرشد الصف', $html);
        $this->assertStringContainsString('مجتهدة في الصف', $html);
        $this->assertStringContainsString('المستوى الأول', $html);
        $this->assertStringContainsString('data:image/jpeg;base64,', $html);
        $this->assertStringNotContainsString('Berlin', $html);
        $this->assertStringNotContainsString('برلين', $html);

        $response = $this->actingAs($admin)
            ->get('/api/admin/academic/students/'.$created['id'].'/pdf?locale=ar');
        $response->assertOk();
        $contentType = (string) $response->headers->get('content-type');
        $body = $response->getContent();
        if (str_contains($contentType, 'html')) {
            $this->assertStringContainsString('فاطمة', $body);
            $this->assertStringContainsString('data:image/jpeg;base64,', $body);
        } else {
            $this->assertStringContainsString('pdf', $contentType);
            $this->assertStringStartsWith('%PDF', $body);
            $this->assertGreaterThan(2000, strlen($body));
        }
    }

    public function test_student_photo_survives_public_disk_wipe(): void
    {
        $admin = $this->superAdmin();
        $created = $this->actingAs($admin)
            ->post('/api/admin/academic/students', [
                'first_name' => 'أحمد',
                'last_name' => 'علي',
                'notes' => 'ملتزم',
                'photo' => UploadedFile::fake()->createWithContent('ahmed.jpg', $this->tinyJpeg()),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data');

        $this->assertTrue(StoredFileStore::isStored($created['photo_path']));
        $this->wipePublicStorage();

        $uuid = StoredFileStore::uuidFromPath($created['photo_path']);
        $this->assertTrue(StoredFile::query()->where('uuid', $uuid)->exists());
        $this->get('/api/public/files/'.$uuid)->assertOk();
    }

    public function test_admin_can_replace_and_remove_student_photo(): void
    {
        $admin = $this->superAdmin();
        $student = Student::query()->create([
            'first_name' => 'سارة',
            'last_name' => 'حسن',
            'status' => 'active',
        ]);

        $updated = $this->actingAs($admin)
            ->post('/api/admin/academic/students/'.$student->id, [
                '_method' => 'PUT',
                'first_name' => 'سارة',
                'last_name' => 'حسن',
                'notes' => 'تحتاج متابعة',
                'photo' => UploadedFile::fake()->createWithContent('sara.jpg', $this->tinyJpeg()),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertSame('تحتاج متابعة', $updated['notes']);
        $this->assertTrue(str_starts_with((string) $updated['photo_path'], 'db:'));
        $this->assertDatabaseCount('stored_files', 1);

        $this->actingAs($admin)
            ->post('/api/admin/academic/students/'.$student->id, [
                '_method' => 'PUT',
                'first_name' => 'سارة',
                'last_name' => 'حسن',
                'notes' => 'تحتاج متابعة',
                'remove_photo' => '1',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.photo_path', null);

        $this->assertDatabaseCount('stored_files', 0);
    }

    private function stageAndLevel(): array
    {
        $stage = EducationStage::query()->create([
            'code' => 'PRIM',
            'name_ar' => 'المرحلة الابتدائية',
            'name_fr' => 'Primaire',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level = Level::query()->create([
            'education_stage_id' => $stage->id,
            'code' => 'L1',
            'name_ar' => 'المستوى الأول',
            'name_fr' => '1re année',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        return [$stage, $level];
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
            if ($file->isDir()) {
                @rmdir($path);
            } else {
                @unlink($path);
            }
        }
    }
}
