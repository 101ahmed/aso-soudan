<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationStage;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStudentRegisterTest extends TestCase
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

    public function test_public_student_registration_appears_in_academic_student_list(): void
    {
        [$level, $subject] = $this->fixtures();

        $this->getJson('/api/public/student-catalog')
            ->assertOk()
            ->assertJsonPath('stages.0.levels.0.id', $level->id)
            ->assertJsonPath('subjects.0.id', $subject->id);

        $this->postJson('/api/public/students', [
            'first_name' => 'آمنة',
            'last_name' => 'حسن',
            'birth_date' => now()->subYears(10)->toDateString(),
            'guardian_name' => 'محمد حسن',
            'guardian_email' => 'parent@example.com',
            'guardian_phone' => '0612345678',
            'level_id' => $level->id,
            'subject_ids' => [$subject->id],
            'consent' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('id', Student::query()->value('id'));

        $this->assertDatabaseHas('students', [
            'first_name' => 'آمنة',
            'last_name' => 'حسن',
            'level_id' => $level->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('guardians', [
            'email' => 'parent@example.com',
            'first_name' => 'محمد',
            'last_name' => 'حسن',
        ]);
        $this->assertSame(1, Guardian::query()->count());
        $this->assertTrue(Student::query()->first()->subjects()->where('subjects.id', $subject->id)->exists());

        $admin = $this->superAdmin();
        $this->actingAs($admin)
            ->getJson('/api/admin/academic/students')
            ->assertOk()
            ->assertJsonPath('data.0.first_name', 'آمنة')
            ->assertJsonPath('data.0.last_name', 'حسن')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonPath('data.0.level_id', $level->id);
    }

    public function test_public_student_registration_requires_consent_and_level(): void
    {
        $this->postJson('/api/public/students', [
            'first_name' => 'آمنة',
            'last_name' => 'حسن',
            'guardian_name' => 'محمد حسن',
            'guardian_email' => 'parent@example.com',
            'consent' => false,
        ])->assertUnprocessable();
    }

    private function fixtures(): array
    {
        AcademicYear::query()->create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'status' => 'active',
            'is_current' => true,
        ]);
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
        $subject = Subject::query()->create([
            'code' => 'QUR',
            'name_ar' => 'القرآن',
            'name_fr' => 'Coran',
            'is_active' => true,
        ]);

        return [$level, $subject];
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'SUPER_ADMIN')->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles');

        return $user;
    }
}
