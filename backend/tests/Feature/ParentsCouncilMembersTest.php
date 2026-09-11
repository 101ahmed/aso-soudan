<?php

namespace Tests\Feature;

use App\Models\CouncilMember;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentsCouncilMembersTest extends TestCase
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

    public function test_admin_can_create_parents_bureau_roles(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/parents/members', [
                'first_name' => 'Hassan',
                'last_name' => 'Omar',
                'position_code' => 'treasurer',
                'is_public' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.council_code', 'parents')
            ->assertJsonPath('data.position_code', 'treasurer')
            ->assertJsonPath('data.position_ar', 'أمين المال')
            ->json('data');

        $this->actingAs($admin)
            ->getJson('/api/public/parents/members')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);

        $this->assertSame('parents', CouncilMember::query()->first()->council_code);
    }

    public function test_parents_president_role_exists(): void
    {
        $this->assertNotNull(Role::query()->where('code', 'PARENTS_PRESIDENT')->first());
        $this->assertNotNull(Role::query()->where('code', 'PARENTS_TREASURER')->first());
        $this->assertNotNull(Role::query()->where('code', 'PARENTS_SECRETARY')->first());
        $this->assertNotNull(Role::query()->where('code', 'PARENTS_VICE_PRESIDENT')->first());
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
