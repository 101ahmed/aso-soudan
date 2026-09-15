<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\EducationStage;
use App\Models\Level;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicExamTest extends TestCase
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

    public function test_admin_creates_exam_enters_grades_and_reads_averages(): void
    {
        $admin = $this->superAdmin();
        [$year, $level, $subject, $student] = $this->fixtures();

        $exam = $this->actingAs($admin)
            ->postJson('/api/admin/academic/exams', [
                'title' => 'امتحان القرآن',
                'academic_year_id' => $year->id,
                'level_id' => $level->id,
                'subject_id' => $subject->id,
                'period' => 'term1',
                'exam_date' => '2026-10-01',
                'max_score' => 20,
                'pass_score' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'امتحان القرآن')
            ->json('data');

        $this->actingAs($admin)
            ->postJson('/api/admin/academic/exams/'.$exam['id'].'/grades', [
                'grades' => [
                    ['student_id' => $student->id, 'score' => 16, 'is_absent' => false],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('roster.0.score', 16)
            ->assertJsonPath('roster.0.passed', true)
            ->assertJsonPath('stats.average', 80);

        $this->actingAs($admin)
            ->getJson('/api/admin/academic/achievement?academic_year_id='.$year->id.'&level_id='.$level->id.'&period=term1')
            ->assertOk()
            ->assertJsonPath('students.0.average', 80)
            ->assertJsonPath('class_average', 80);

        $this->actingAs($admin)
            ->getJson('/api/admin/academic/students/'.$student->id.'/academic-report')
            ->assertOk()
            ->assertJsonPath('overall_average', 80)
            ->assertJsonPath('subjects.0.average', 80);

        $this->actingAs($admin)
            ->get('/api/admin/academic/exams/pdf?locale=ar')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/api/admin/academic/exams/'.$exam['id'].'/pdf')
            ->assertOk();
        $this->actingAs($admin)
            ->get('/api/admin/academic/achievement/pdf?academic_year_id='.$year->id.'&level_id='.$level->id.'&period=term1')
            ->assertOk();
    }

    public function test_teacher_can_edit_grades_only_for_assigned_subject(): void
    {
        $admin = $this->superAdmin();
        [$year, $level, $subject, $student] = $this->fixtures();
        $other = Subject::query()->create([
            'code' => 'AR',
            'name_ar' => 'العربية',
            'name_fr' => 'Arabe',
            'is_active' => true,
        ]);

        $exam = $this->actingAs($admin)
            ->postJson('/api/admin/academic/exams', [
                'title' => 'اختبار',
                'academic_year_id' => $year->id,
                'level_id' => $level->id,
                'subject_id' => $subject->id,
                'period' => 'term1',
                'exam_date' => '2026-10-02',
                'max_score' => 10,
                'pass_score' => 5,
            ])
            ->assertCreated()
            ->json('data');

        $blockedExam = $this->actingAs($admin)
            ->postJson('/api/admin/academic/exams', [
                'title' => 'عربية',
                'academic_year_id' => $year->id,
                'level_id' => $level->id,
                'subject_id' => $other->id,
                'period' => 'term1',
                'exam_date' => '2026-10-03',
                'max_score' => 10,
                'pass_score' => 5,
            ])
            ->assertCreated()
            ->json('data');

        $teacherUser = User::factory()->create();
        $role = Role::query()->where('code', 'TEACHER')->firstOrFail();
        $teacherUser->roles()->attach($role->id);
        $teacher = Teacher::query()->create([
            'user_id' => $teacherUser->id,
            'first_name' => 'معلم',
            'last_name' => 'تجربة',
            'status' => 'active',
        ]);
        $teacher->subjects()->attach($subject->id);
        $teacher->levels()->attach($level->id);
        $teacherUser->load('roles');

        $this->actingAs($teacherUser)
            ->postJson('/api/admin/academic/exams/'.$exam['id'].'/grades', [
                'grades' => [['student_id' => $student->id, 'score' => 8]],
            ])
            ->assertOk()
            ->assertJsonPath('roster.0.score', 8);

        $this->actingAs($teacherUser)
            ->postJson('/api/admin/academic/exams/'.$blockedExam['id'].'/grades', [
                'grades' => [['student_id' => $student->id, 'score' => 8]],
            ])
            ->assertForbidden();
    }

    private function fixtures(): array
    {
        $year = AcademicYear::query()->create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'status' => 'active',
            'is_current' => true,
        ]);
        $stage = EducationStage::query()->create([
            'code' => 'PRIM',
            'name_ar' => 'ابتدائي',
            'name_fr' => 'Primaire',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level = Level::query()->create([
            'education_stage_id' => $stage->id,
            'code' => 'L1',
            'name_ar' => 'المستوى 1',
            'name_fr' => 'Niveau 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $subject = Subject::query()->create([
            'code' => 'QUR',
            'name_ar' => 'القرآن',
            'name_fr' => 'Coran',
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'first_name' => 'فاطمة',
            'last_name' => 'محمد',
            'status' => 'active',
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
        ]);

        return [$year, $level, $subject, $student];
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
