<?php

namespace Tests\Feature;

use App\Models\DailyStudentAttendance;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherRegisterTest extends TestCase
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

    public function test_rename_updates_only_the_student_name_and_keeps_attendance(): void
    {
        $admin = $this->superAdmin();
        $student = Student::query()->create([
            'first_name' => 'أحمد',
            'last_name' => 'علي',
            'status' => 'active',
            'notes' => 'لا تُمس',
        ]);
        $id = $student->id;

        DailyStudentAttendance::query()->create([
            'student_id' => $student->id,
            'attendance_date' => '2026-08-30',
            'status' => StudentAttendance::STATUS_PRESENT,
            'recorded_by' => $admin->id,
        ]);

        $payload = $this->actingAs($admin)
            ->patchJson('/api/admin/academic/register/'.$student->id, [
                'first_name' => 'محمد',
                'last_name' => 'حسن',
                'status' => 'archived',
                'notes' => 'يجب تجاهلها',
            ])
            ->assertOk()
            ->json();

        $this->assertSame($id, $payload['id']);
        $this->assertSame('محمد', $payload['first_name']);
        $this->assertSame('حسن', $payload['last_name']);
        $this->assertSame('محمد حسن', $payload['full_name']);

        $student->refresh();
        $this->assertSame($id, $student->id);
        $this->assertSame('محمد', $student->first_name);
        $this->assertSame('حسن', $student->last_name);
        $this->assertSame('active', $student->status);
        $this->assertSame('لا تُمس', $student->notes);

        $this->assertDatabaseHas('daily_student_attendances', [
            'student_id' => $id,
            'attendance_date' => '2026-08-30',
            'status' => StudentAttendance::STATUS_PRESENT,
        ]);
    }

    public function test_register_and_pdf_only_include_sunday(): void
    {
        $admin = $this->superAdmin();

        $json = $this->actingAs($admin)
            ->getJson('/api/admin/academic/register?from=2026-08-29&to=2026-09-04')
            ->assertOk()
            ->json();

        $this->assertSame('2026-08-30', $json['from']);
        $this->assertSame('2026-08-30', $json['to']);

        $response = $this->actingAs($admin)
            ->get('/api/admin/academic/register/pdf?from=2026-08-29&to=2026-09-04&locale=ar');

        $response->assertOk();

        $contentType = (string) $response->headers->get('content-type');
        if (str_contains($contentType, 'html')) {
            $html = $response->getContent();
            $this->assertStringContainsString('الأحد', $html);
            $this->assertStringContainsString('30/08', $html);
            $this->assertStringNotContainsString('29/08', $html);
            $this->assertStringNotContainsString('السبت', $html);
            $this->assertStringNotContainsString('الإثنين', $html);
        } else {
            $this->assertStringContainsString('pdf', $contentType);
        }
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
