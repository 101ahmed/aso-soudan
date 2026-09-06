<?php

namespace Tests\Feature;

use App\Models\EducationStage;
use App\Models\LessonPreparation;
use App\Models\Level;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPreparationTest extends TestCase
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

    public function test_teacher_can_create_update_and_delete_own_lesson_prep(): void
    {
        [$teacherUser] = $this->makeTeacher('noura@acs-rennes.fr');
        [$subject, $level] = $this->catalog();

        $created = $this->actingAs($teacherUser)
            ->postJson('/api/admin/academic/lesson-preparations', [
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'lesson_date' => '2026-09-13',
                'title' => 'أقسام الكلام',
                'unit' => 'النحو',
                'objectives' => 'تمييز الاسم والفعل',
                'skills' => 'التصنيف',
                'concepts' => 'الاسم، الفعل، الحرف',
                'intro' => 'أسئلة تمهيدية',
                'explanation' => 'شرح الأمثلة',
                'activities' => 'بطاقة تصنيف',
                'group_work' => 'عمل ثنائي',
                'assessment' => 'سؤال شفهي',
                'conclusion' => 'واجب: جملتان',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertSame('أقسام الكلام', $created['title']);
        $this->assertSame('النحو', $created['unit']);
        $this->assertSame('2026-09-13', $created['lesson_date']);
        $this->assertSame($subject->id, $created['subject_id']);
        $this->assertSame($level->id, $created['level_id']);

        $id = $created['id'];

        $this->actingAs($teacherUser)
            ->putJson('/api/admin/academic/lesson-preparations/'.$id, [
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'lesson_date' => '2026-09-20',
                'title' => 'أقسام الكلام — مراجعة',
                'conclusion' => 'واجب: ثلاث جمل',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'أقسام الكلام — مراجعة')
            ->assertJsonPath('data.lesson_date', '2026-09-20');

        $list = $this->actingAs($teacherUser)
            ->getJson('/api/admin/academic/lesson-preparations')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $list);
        $this->assertSame($id, $list[0]['id']);

        $this->actingAs($teacherUser)
            ->deleteJson('/api/admin/academic/lesson-preparations/'.$id)
            ->assertOk();

        $this->assertSoftDeleted('lesson_preparations', ['id' => $id]);
    }

    public function test_teacher_cannot_access_another_teachers_prep(): void
    {
        [$owner] = $this->makeTeacher('owner@acs-rennes.fr');
        [$other] = $this->makeTeacher('other@acs-rennes.fr');
        [$subject, $level] = $this->catalog();

        $prep = LessonPreparation::query()->create([
            'teacher_id' => $owner->teacher->id,
            'created_by_user_id' => $owner->id,
            'subject_id' => $subject->id,
            'level_id' => $level->id,
            'lesson_date' => '2026-09-13',
            'title' => 'خاص',
        ]);

        $this->actingAs($other)
            ->getJson('/api/admin/academic/lesson-preparations')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($other)
            ->getJson('/api/admin/academic/lesson-preparations/'.$prep->id)
            ->assertForbidden();

        $this->actingAs($other)
            ->putJson('/api/admin/academic/lesson-preparations/'.$prep->id, [
                'subject_id' => $subject->id,
                'level_id' => $level->id,
                'lesson_date' => '2026-09-13',
                'title' => 'اختراق',
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->deleteJson('/api/admin/academic/lesson-preparations/'.$prep->id)
            ->assertForbidden();
    }

    public function test_unrelated_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', 'MEMBER')->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles');

        $this->actingAs($user)
            ->getJson('/api/admin/academic/lesson-preparations')
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Teacher}
     */
    private function makeTeacher(string $email): array
    {
        $user = User::factory()->create(['email' => $email]);
        $role = Role::query()->where('code', 'TEACHER')->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles.permissions');

        $teacher = Teacher::query()->create([
            'user_id' => $user->id,
            'first_name' => 'معلم',
            'last_name' => explode('@', $email)[0],
            'status' => 'active',
        ]);
        $user->setRelation('teacher', $teacher);

        return [$user->fresh()->load('roles.permissions', 'teacher'), $teacher];
    }

    /**
     * @return array{0: Subject, 1: Level}
     */
    private function catalog(): array
    {
        $stage = EducationStage::query()->create([
            'code' => 'PRIMARY',
            'name_ar' => 'ابتدائي',
            'name_fr' => 'Primaire',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $level = Level::query()->create([
            'education_stage_id' => $stage->id,
            'code' => 'L1',
            'name_ar' => 'المستوى الأول',
            'name_fr' => 'Niveau 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $subject = Subject::query()->create([
            'code' => 'AR',
            'name_ar' => 'اللغة العربية',
            'name_fr' => 'Arabe',
            'is_active' => true,
        ]);

        return [$subject, $level];
    }
}
