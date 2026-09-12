<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialVisitTest extends TestCase
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

    public function test_admin_can_create_update_and_filter_social_visits(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/departments/social/visits', [
                'full_name' => 'أسرة أحمد',
                'place' => 'Rennes',
                'visit_type' => 'home',
                'reason' => 'متابعة بعد طلب مساعدة',
                'visited_on' => '2026-09-12',
                'visited_at' => '15:30',
                'visitors' => 'أمين الأمانة الاجتماعية',
                'status' => 'planned',
            ])
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'أسرة أحمد')
            ->assertJsonPath('data.visit_type', 'home')
            ->assertJsonPath('data.visited_at', '15:30')
            ->json('data');

        $this->actingAs($admin)
            ->putJson('/api/admin/departments/social/visits/'.$created['id'], [
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/social/visits?visit_type=home')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/academic/visits')
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
}
