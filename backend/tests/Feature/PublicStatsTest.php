<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Event;
use App\Models\Member;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not available in this PHP build.');
        }

        parent::setUp();

        $this->seed(DepartmentSeeder::class);
    }

    public function test_public_stats_count_only_active_and_published_records(): void
    {
        Member::query()->create([
            'first_name' => 'Active',
            'last_name' => 'Member',
            'status' => 'active',
        ]);
        Member::query()->create([
            'first_name' => 'Pending',
            'last_name' => 'Member',
            'status' => 'pending',
        ]);
        Member::query()->create([
            'first_name' => 'Volunteer',
            'last_name' => 'Member',
            'status' => 'active',
            'membership_type' => 'volunteer',
        ]);

        Student::query()->create([
            'first_name' => 'Active',
            'last_name' => 'Student',
            'status' => 'active',
        ]);
        Student::query()->create([
            'first_name' => 'Archived',
            'last_name' => 'Student',
            'status' => 'archived',
        ]);

        $teacherUser = User::factory()->create();
        Teacher::query()->create([
            'user_id' => $teacherUser->id,
            'first_name' => 'Active',
            'last_name' => 'Teacher',
            'status' => 'active',
        ]);

        $social = Department::query()->where('code', 'social')->firstOrFail();
        $academic = Department::query()->where('code', 'academic')->firstOrFail();

        Event::query()->create([
            'title_ar' => 'فعالية اجتماعية',
            'title_fr' => 'Initiative sociale',
            'department_id' => $social->id,
            'status' => 'published',
            'published_at' => now(),
            'type' => 'activity',
        ]);
        Event::query()->create([
            'title_ar' => 'مسودة',
            'title_fr' => 'Brouillon',
            'department_id' => $academic->id,
            'status' => 'draft',
            'type' => 'seminar',
        ]);
        Event::query()->create([
            'title_ar' => 'ندوة أكاديمية',
            'title_fr' => 'Séminaire académique',
            'department_id' => $academic->id,
            'status' => 'published',
            'published_at' => now(),
            'type' => 'seminar',
        ]);

        $this->getJson('/api/public/stats')
            ->assertOk()
            ->assertJsonPath('members', 2)
            ->assertJsonPath('students', 1)
            ->assertJsonPath('teachers', 1)
            ->assertJsonPath('volunteers', 1)
            ->assertJsonPath('teachers_and_volunteers', 2)
            ->assertJsonPath('events', 2)
            ->assertJsonPath('initiatives', 1)
            ->assertJsonPath('programs', 1)
            ->assertJsonPath('academic_events', 1)
            ->assertJsonPath('activities', 1);
    }
}
