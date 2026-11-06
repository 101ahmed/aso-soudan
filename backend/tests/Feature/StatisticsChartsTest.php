<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsChartsTest extends TestCase
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

    public function test_admin_can_read_statistics_charts(): void
    {
        $admin = $this->superAdmin();

        $this->createMember([
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'city' => 'Rennes',
            'gender' => 'male',
            'membership_type' => 'adherent',
            'status' => 'active',
        ], now()->startOfMonth());
        $this->createMember([
            'first_name' => 'Sara',
            'last_name' => 'Hassan',
            'city' => 'Betton',
            'gender' => 'female',
            'membership_type' => 'volunteer',
            'status' => 'active',
        ], now()->startOfMonth());
        $this->createMember([
            'first_name' => 'Omar',
            'last_name' => 'Saleh',
            'city' => 'Rennes',
            'gender' => 'male',
            'status' => 'pending',
        ], now()->subMonths(2)->startOfMonth());

        $this->actingAs($admin)
            ->getJson('/api/admin/statistics/charts')
            ->assertOk()
            ->assertJsonPath('kpis.members_total', 3)
            ->assertJsonPath('kpis.members_active', 2)
            ->assertJsonFragment(['key' => 'male', 'total' => 2])
            ->assertJsonFragment(['key' => 'female', 'total' => 1])
            ->assertJsonFragment(['key' => 'Rennes', 'total' => 2])
            ->assertJsonFragment(['key' => 'Betton', 'total' => 1])
            ->assertJsonFragment(['key' => now()->format('Y-m'), 'total' => 2]);
    }

    public function test_guest_cannot_read_statistics_charts(): void
    {
        $this->getJson('/api/admin/statistics/charts')->assertUnauthorized();
    }

    private function createMember(array $attributes, $createdAt): Member
    {
        $member = Member::query()->create($attributes);
        $member->created_at = $createdAt;
        $member->save();

        return $member;
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
