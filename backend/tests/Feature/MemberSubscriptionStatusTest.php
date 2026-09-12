<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberSubscriptionStatusTest extends TestCase
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

    public function test_admin_can_set_first_second_and_full_subscription(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/statistics/members', [
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'city' => 'Rennes',
                'status' => 'active',
                'subscription_status' => 'first',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subscription_status', 'first')
            ->json('data');

        $this->actingAs($admin)
            ->putJson('/api/admin/statistics/members/'.$created['id'], [
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'city' => 'Rennes',
                'status' => 'active',
                'subscription_status' => 'second',
            ])
            ->assertOk()
            ->assertJsonPath('data.subscription_status', 'second');

        $this->actingAs($admin)
            ->putJson('/api/admin/statistics/members/'.$created['id'], [
                'first_name' => 'Ahmed',
                'last_name' => 'Ali',
                'city' => 'Rennes',
                'status' => 'active',
                'subscription_status' => 'full',
            ])
            ->assertOk()
            ->assertJsonPath('data.subscription_status', 'full');

        $this->actingAs($admin)
            ->getJson('/api/admin/statistics/members?subscription_status=full')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);
    }

    public function test_new_member_defaults_to_unpaid(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->postJson('/api/admin/statistics/members', [
                'first_name' => 'Sara',
                'last_name' => 'Hassan',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subscription_status', 'unpaid');

        $this->assertSame('unpaid', Member::query()->first()->subscription_status);
        $this->assertEquals(0, (float) Member::query()->first()->amount_paid);
    }

    public function test_admin_can_set_amount_paid(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/statistics/members', [
                'first_name' => 'Omar',
                'last_name' => 'Saleh',
                'status' => 'active',
                'amount_paid' => 45.5,
            ])
            ->assertCreated()
            ->assertJsonPath('data.amount_paid', 45.5)
            ->json('data');

        $this->actingAs($admin)
            ->putJson('/api/admin/statistics/members/'.$created['id'], [
                'first_name' => 'Omar',
                'last_name' => 'Saleh',
                'status' => 'active',
                'amount_paid' => 90,
            ])
            ->assertOk()
            ->assertJsonPath('data.amount_paid', 90);

        $this->assertDatabaseHas('finance_revenues', [
            'member_id' => $created['id'],
            'source' => 'membership',
            'amount' => 90,
        ]);
    }

    public function test_admin_can_set_and_filter_marital_status(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/statistics/members', [
                'first_name' => 'Nour',
                'last_name' => 'Idris',
                'status' => 'active',
                'marital_status' => 'married',
            ])
            ->assertCreated()
            ->assertJsonPath('data.marital_status', 'married')
            ->json('data');

        $this->actingAs($admin)
            ->getJson('/api/admin/statistics/members?marital_status=married')
            ->assertOk()
            ->assertJsonPath('data.0.id', $created['id']);
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
