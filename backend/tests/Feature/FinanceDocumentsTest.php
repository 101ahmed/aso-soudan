<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\StoredFileStore;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FinanceDocumentsTest extends TestCase
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

    public function test_admin_lists_the_two_finance_document_slots(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson('/api/admin/departments/finance/finance/documents')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['kind' => 'general_report', 'is_published' => false])
            ->assertJsonFragment(['kind' => 'subscriptions_announcement', 'is_published' => false]);
    }

    public function test_unpublished_documents_are_hidden_from_the_public(): void
    {
        $this->getJson('/api/public/finance/documents')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/public/finance/documents/general_report')
            ->assertNotFound();
    }

    public function test_publish_requires_file_or_text(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson('/api/admin/departments/finance/finance/documents/general_report/publish')
            ->assertStatus(422);
    }

    public function test_admin_can_upload_publish_and_public_pages_receive_the_file(): void
    {
        $admin = $this->superAdmin();
        $pdf = UploadedFile::fake()->createWithContent('report.pdf', '%PDF-1.4 finance-report');

        $updated = $this->actingAs($admin)
            ->post('/api/admin/departments/finance/finance/documents/general_report', [
                'title_ar' => 'تقرير مالي عام',
                'title_fr' => 'Rapport financier public',
                'body_fr' => 'Synthèse 2026',
                'file' => $pdf,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertTrue(str_starts_with((string) $updated['file_path'], 'db:'));
        $this->assertStringContainsString('/api/public/files/', (string) $updated['file_url']);
        $this->assertDatabaseCount('stored_files', 1);

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/finance/finance/documents/general_report/publish')
            ->assertOk()
            ->assertJsonPath('data.is_published', true);

        $public = $this->getJson('/api/public/finance/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kind', 'general_report')
            ->json('data.0');

        $this->assertStringContainsString('/api/public/files/', (string) $public['file_url']);

        $this->get($public['file_url'])
            ->assertOk()
            ->assertSee('%PDF-1.4 finance-report', false);
    }

    public function test_subscriptions_announcement_is_public_only_after_publish(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/api/admin/departments/finance/finance/documents/subscriptions_announcement', [
                'title_ar' => 'إعلان الاشتراكات',
                'title_fr' => 'Annonce des cotisations',
                'body_ar' => 'الاشتراك السنوي 20 يورو',
                'body_fr' => 'Cotisation annuelle 20 €',
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->getJson('/api/public/finance/documents/subscriptions_announcement')
            ->assertNotFound();

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/finance/finance/documents/subscriptions_announcement/publish')
            ->assertOk();

        $this->getJson('/api/public/finance/documents/subscriptions_announcement')
            ->assertOk()
            ->assertJsonPath('data.kind', 'subscriptions_announcement')
            ->assertJsonPath('data.body_fr', 'Cotisation annuelle 20 €');

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/finance/finance/documents/subscriptions_announcement/unpublish')
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        $this->getJson('/api/public/finance/documents/subscriptions_announcement')
            ->assertNotFound();
    }

    public function test_uploaded_finance_file_survives_public_disk_wipe(): void
    {
        $admin = $this->superAdmin();
        $pdf = UploadedFile::fake()->createWithContent('annonce.pdf', '%PDF-1.4 subscriptions');

        $payload = $this->actingAs($admin)
            ->post('/api/admin/departments/finance/finance/documents/subscriptions_announcement', [
                'title_ar' => 'إعلان الاشتراكات',
                'file' => $pdf,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->json('data');

        $this->assertTrue(StoredFileStore::isStored($payload['file_path']));
        $this->assertDatabaseCount('stored_files', 1);

        $this->wipePublicStorage();

        $uuid = StoredFileStore::uuidFromPath($payload['file_path']);
        $this->assertNotNull($uuid);
        $this->assertTrue(StoredFile::query()->where('uuid', $uuid)->exists());

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/finance/finance/documents/subscriptions_announcement/publish')
            ->assertOk();

        $this->get('/api/public/files/'.$uuid)
            ->assertOk()
            ->assertSee('%PDF-1.4 subscriptions', false);
    }

    public function test_non_finance_department_cannot_manage_finance_documents(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson('/api/admin/departments/academic/finance/documents')
            ->assertNotFound();
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
