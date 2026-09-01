<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresidentialWorkspaceTest extends TestCase
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

    public function test_president_can_create_meeting_send_directive_and_search_archive(): void
    {
        $president = $this->userWithRole('PRESIDENT');
        $manager = $this->userWithRole('GENERAL_SECRETARIAT');
        $general = Department::query()->where('code', 'general')->firstOrFail();
        $manager->departments()->sync([$general->id => ['is_primary' => true]]);

        $this->actingAs($president);

        $meeting = $this->postJson('/api/admin/president/meetings', [
            'title_ar' => 'اجتماع تنسيقي',
            'title_fr' => 'Réunion de coordination',
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'location' => 'رين',
            'classification' => 'urgent',
            'status' => 'upcoming',
            'agenda_ar' => 'بند الميزانية',
            'agenda_fr' => 'Budget',
            'minutes_ar' => 'تمت مناقشة الميزانية',
            'minutes_fr' => 'Budget discuté',
            'decisions_ar' => 'اعتماد التقرير',
            'decisions_fr' => 'Approuver le rapport',
            'follow_up_ar' => 'متابعة الأمانة المالية',
            'follow_up_fr' => 'Suivi finance',
        ])->assertCreated()->json('data');

        $this->assertNotEmpty($meeting['reference']);
        $this->assertSame('urgent', $meeting['classification']);

        $directive = $this->postJson('/api/admin/president/directives', [
            'department_id' => $general->id,
            'body' => 'يرجى موافاتنا بتقرير المتابعة خلال أسبوع.',
            'classification' => 'follow_up',
            'title' => 'متابعة مالية',
        ])->assertCreated()->json('data');

        $this->assertSame($manager->id, $directive['assignee']['id']);
        $this->assertSame('general', $directive['department']['code']);

        $this->getJson('/api/admin/president/archive?decision_number='.urlencode($meeting['decision_number']))
            ->assertOk()
            ->assertJsonFragment(['category' => 'decision']);

        $this->getJson('/api/admin/president/archive?date_from='.now()->toDateString())
            ->assertOk()
            ->assertJsonFragment(['category' => 'directive']);

        $this->actingAs($manager);
        $inbox = $this->getJson('/api/admin/departments/general/presidential-directives')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $inbox);
        $this->assertSame('متابعة مالية', $inbox[0]['title']);
    }

    public function test_member_cannot_access_president_workspace(): void
    {
        $member = $this->userWithRole('MEMBER');
        $this->actingAs($member)
            ->getJson('/api/admin/president/meetings')
            ->assertForbidden();
    }

    private function userWithRole(string $code): User
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $code)->firstOrFail();
        $user->roles()->attach($role->id);
        $user->load('roles');

        return $user;
    }
}
