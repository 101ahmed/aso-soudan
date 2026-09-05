<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SecretariatMeetingOutput;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretariatMeetingOutputTest extends TestCase
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

    public function test_admin_can_create_and_list_meeting_outputs(): void
    {
        $admin = $this->superAdmin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/departments/general/meeting-outputs', $this->payload([
                'title_ar' => 'اجتماع المكتب',
                'title_fr' => 'Réunion du bureau',
                'outputs_ar' => 'اعتماد التقرير',
                'outputs_fr' => 'Adoption du rapport',
                'is_public' => false,
            ]))
            ->assertCreated()
            ->assertJsonPath('data.title_fr', 'Réunion du bureau')
            ->assertJsonPath('data.is_public', false)
            ->json('data');

        $this->assertNotEmpty($created['reference']);
        $this->assertStringStartsWith('MO-', $created['reference']);

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/general/meeting-outputs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $created['id'])
            ->assertJsonPath('data.0.outputs_fr', 'Adoption du rapport');

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/general/meeting-outputs/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.reference', $created['reference']);
    }

    public function test_non_general_secretariat_returns_not_found(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson('/api/admin/departments/academic/meeting-outputs')
            ->assertNotFound();

        $this->actingAs($admin)
            ->postJson('/api/admin/departments/media/meeting-outputs', $this->payload())
            ->assertNotFound();

        $this->getJson('/api/public/secretariats/academic/meeting-outputs')
            ->assertNotFound();
    }

    public function test_public_endpoint_hides_private_outputs(): void
    {
        $admin = $this->superAdmin();

        SecretariatMeetingOutput::query()->create([
            ...$this->payload([
                'title_ar' => 'اجتماع داخلي',
                'title_fr' => 'Réunion interne',
                'is_public' => false,
            ]),
            'recorded_by' => $admin->id,
        ]);

        $public = SecretariatMeetingOutput::query()->create([
            ...$this->payload([
                'title_ar' => 'اجتماع عام',
                'title_fr' => 'Réunion publique',
                'meeting_on' => '2026-09-01',
                'outputs_fr' => 'Conclusions publiques',
                'is_public' => true,
            ]),
            'recorded_by' => $admin->id,
        ]);

        $this->getJson('/api/public/secretariats/general/meeting-outputs')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $public->id)
            ->assertJsonPath('data.0.title_fr', 'Réunion publique')
            ->assertJsonMissingPath('data.0.notes');
    }

    public function test_admin_can_update_publish_and_delete(): void
    {
        $admin = $this->superAdmin();

        $item = SecretariatMeetingOutput::query()->create([
            ...$this->payload(['is_public' => false]),
            'recorded_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/departments/general/meeting-outputs/'.$item->id, [
                'is_public' => true,
                'follow_up_fr' => 'Compte rendu à envoyer',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_public', true)
            ->assertJsonPath('data.follow_up_fr', 'Compte rendu à envoyer');

        $this->getJson('/api/public/secretariats/general/meeting-outputs')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($admin)
            ->deleteJson('/api/admin/departments/general/meeting-outputs/'.$item->id)
            ->assertOk();

        $this->assertSoftDeleted('secretariat_meeting_outputs', ['id' => $item->id]);

        $this->getJson('/api/public/secretariats/general/meeting-outputs')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title_ar' => 'اجتماع الأمانة العامة',
            'title_fr' => 'Réunion de l’amanah générale',
            'meeting_on' => '2026-08-20',
            'location' => 'Rennes',
            'attendees_ar' => 'الأمين العام والأعضاء',
            'attendees_fr' => 'Amin général et membres',
            'agenda_ar' => 'جدول الأعمال',
            'agenda_fr' => 'Ordre du jour',
            'outputs_ar' => 'المخرجات',
            'outputs_fr' => 'Conclusions',
            'follow_up_ar' => null,
            'follow_up_fr' => null,
            'notes' => 'Note interne',
            'is_public' => false,
        ], $overrides);
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
