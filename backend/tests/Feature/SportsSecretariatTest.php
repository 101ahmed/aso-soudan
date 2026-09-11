<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SportsJoinRequest;
use App\Models\SportsTeam;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SportsSecretariatTest extends TestCase
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

    public function test_admin_can_create_public_team_and_list_it(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/departments/sports/sports/teams', [
                'name_ar' => 'فريق الأشبال',
                'name_fr' => 'U13 Rennes',
                'age_category' => 'u13',
                'coach_ar' => 'مدرب',
                'coach_fr' => 'Coach',
                'manager_ar' => 'مسؤول',
                'manager_fr' => 'Responsable',
                'is_public' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name_fr', 'U13 Rennes')
            ->assertJsonPath('data.is_public', true)
            ->json('data');

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/sports/sports/teams')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);

        $this->getJson('/api/public/sports')
            ->assertOk()
            ->assertJsonPath('teams.0.id', $created['id']);
    }

    public function test_public_hides_private_teams(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/sports/sports/teams', [
                'name_ar' => 'خاص',
                'name_fr' => 'Privé',
                'age_category' => 'seniors',
                'is_public' => false,
            ])
            ->assertCreated();

        $this->getJson('/api/public/sports')
            ->assertOk()
            ->assertJsonCount(0, 'teams');
    }

    public function test_public_can_submit_join_request(): void
    {
        $this->postJson('/api/public/sports/join', [
            'full_name' => 'Ahmed Test',
            'phone' => '0600000000',
            'age_category' => 'u15',
            'message' => 'Je souhaite rejoindre.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Join request received.')
            ->assertJsonStructure(['reference']);

        $this->assertSame(1, SportsJoinRequest::query()->count());
        $this->assertStringStartsWith('SP-', SportsJoinRequest::query()->first()->reference);
    }

    public function test_non_sports_secretariat_returns_not_found(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/academic/sports/teams')
            ->assertNotFound();

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/social/sports/teams', [
                'name_ar' => 'X',
                'name_fr' => 'X',
                'age_category' => 'u11',
            ])
            ->assertNotFound();
    }

    public function test_admin_can_add_national_player_visible_publicly(): void
    {
        $admin = $this->superAdmin();
        $team = SportsTeam::query()->create([
            'name_ar' => 'منتخب',
            'name_fr' => 'Sélection',
            'age_category' => 'seniors',
            'is_public' => true,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/sports/sports/players', [
                'name_ar' => 'هداف',
                'name_fr' => 'Buteur',
                'sports_team_id' => $team->id,
                'is_national' => true,
                'is_public' => true,
                'goals' => 12,
            ])
            ->assertCreated();

        $this->getJson('/api/public/sports/national')
            ->assertOk()
            ->assertJsonPath('players.0.name_fr', 'Buteur')
            ->assertJsonPath('scorers.0.goals', 12);
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
