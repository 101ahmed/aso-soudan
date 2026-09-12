<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassStaffAssignmentTest extends TestCase
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

    public function test_admin_can_assign_supervisor_and_counselor_per_level(): void
    {
        $admin = $this->superAdmin();
        [$level] = $this->stageAndLevel();
        $year = $this->currentYear();

        $this->actingAs($admin)
            ->getJson('/api/admin/academic/class-staff')
            ->assertOk()
            ->assertJsonPath('academic_year.id', $year->id)
            ->assertJsonPath('levels.0.id', $level->id)
            ->assertJsonPath('levels.0.supervisor_name', null)
            ->assertJsonPath('levels.0.counselor_name', null);

        $this->actingAs($admin)
            ->putJson('/api/admin/academic/class-staff/'.$level->id, [
                'supervisor_name' => 'خالد أحمد',
                'counselor_name' => 'سارة حسن',
            ])
            ->assertOk()
            ->assertJsonPath('supervisor.full_name', 'خالد أحمد')
            ->assertJsonPath('counselor.full_name', 'سارة حسن');

        $this->actingAs($admin)
            ->putJson('/api/admin/academic/class-staff/'.$level->id.'/visits', [
                'month' => '2026-09',
                'visited_on' => '2026-09-12',
            ])
            ->assertOk()
            ->assertJsonPath('month', '2026-09')
            ->assertJsonPath('visited_on', '2026-09-12');

        $student = Student::query()->create([
            'first_name' => 'فاطمة',
            'last_name' => 'محمد',
            'status' => 'active',
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/academic/students/'.$student->id)
            ->assertOk()
            ->assertJsonPath('data.class_supervisor.full_name', 'خالد أحمد')
            ->assertJsonPath('data.class_counselor.full_name', 'سارة حسن')
            ->assertJsonPath('data.supervisor_last_visit.visited_on', '2026-09-12');

        $html = view('reports.student-dossier', [
            'locale' => 'ar',
            'student' => tap(
                Student::query()->with(['level', 'educationStage', 'academicYear', 'subjects'])->findOrFail($student->id),
                function (Student $loaded) {
                    \App\Models\ClassStaffAssignment::attachToStudents([$loaded]);
                }
            ),
            'photoSrc' => null,
        ])->render();

        $this->assertStringContainsString('موجه الصف', $html);
        $this->assertStringContainsString('مرشد الصف', $html);
        $this->assertStringContainsString('خالد أحمد', $html);
        $this->assertStringContainsString('سارة حسن', $html);
    }

    /**
     * @return array{0: Level}
     */
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

        return [$level];
    }

    private function currentYear(): AcademicYear
    {
        return AcademicYear::query()->create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'status' => 'active',
            'is_current' => true,
        ]);
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
