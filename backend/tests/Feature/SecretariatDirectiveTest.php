<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\SecretariatDirective;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecretariatDirectiveTest extends TestCase
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

    public function test_secretariat_can_send_a_directive_to_one_amanah(): void
    {
        [$academic] = $this->officer('ACADEMIC_SECRETARIAT', 'academic');
        [$social, $socialDept] = $this->officer('SOCIAL_SECRETARIAT', 'social');

        $created = $this->actingAs($academic)
            ->postJson('/api/admin/departments/academic/secretariat-directives', [
                'recipient' => 'one',
                'recipient_department_id' => $socialDept->id,
                'title' => 'تنسيق رمضان',
                'body' => 'يرجى موافاتنا بخطة الأنشطة الرمضانية.',
                'classification' => 'follow_up',
            ])
            ->assertCreated()
            ->json('data');

        $this->assertCount(1, $created);
        $this->assertSame('social', $created[0]['recipient_department']['code']);
        $this->assertSame($social->id, $created[0]['assignee']['id']);
        $this->assertFalse($created[0]['is_broadcast']);

        $inbox = $this->actingAs($social)
            ->getJson('/api/admin/departments/social/secretariat-directives')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $inbox);
        $this->assertSame('تنسيق رمضان', $inbox[0]['title']);
        $this->assertSame('academic', $inbox[0]['sender_department']['code']);
    }

    public function test_secretariat_can_broadcast_a_directive_to_all_amanahs(): void
    {
        [$general] = $this->officer('GENERAL_SECRETARIAT', 'general');
        $this->officer('ACADEMIC_SECRETARIAT', 'academic');
        $this->officer('SPORTS_SECRETARIAT', 'sports');

        $created = $this->actingAs($general)
            ->postJson('/api/admin/departments/general/secretariat-directives', [
                'recipient' => 'all',
                'body' => 'اجتماع تنسيقي يوم الأحد.',
                'classification' => 'urgent',
            ])
            ->assertCreated()
            ->json('data');

        $recipientCodes = collect($created)->pluck('recipient_department.code')->sort()->values();
        $this->assertNotContains('general', $recipientCodes->all());
        $this->assertTrue($recipientCodes->contains('academic'));
        $this->assertTrue($created[0]['is_broadcast']);
        $this->assertNotEmpty($created[0]['broadcast_id']);
        $this->assertSame(
            $created[0]['broadcast_id'],
            $created[1]['broadcast_id'] ?? null
        );

        $this->assertSame(
            count($created),
            SecretariatDirective::query()->where('sender_department_id', Department::query()->where('code', 'general')->value('id'))->count()
        );
    }

    public function test_recipient_can_mark_directive_as_read(): void
    {
        [$academic] = $this->officer('ACADEMIC_SECRETARIAT', 'academic');
        [$media, $mediaDept] = $this->officer('MEDIA_SECRETARIAT', 'media');

        $item = $this->actingAs($academic)
            ->postJson('/api/admin/departments/academic/secretariat-directives', [
                'recipient' => 'one',
                'recipient_department_id' => $mediaDept->id,
                'body' => 'نرجو تغطية الفعالية.',
            ])
            ->json('data.0');

        $updated = $this->actingAs($media)
            ->putJson('/api/admin/departments/media/secretariat-directives/'.$item['id'], [
                'status' => 'read',
            ])
            ->assertOk()
            ->json('data');

        $this->assertSame('read', $updated['status']);
        $this->assertNotEmpty($updated['read_at']);
    }

    /**
     * @return array{0: User, 1: Department}
     */
    private function officer(string $roleCode, string $departmentCode): array
    {
        $user = User::factory()->create();
        $role = Role::query()->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role->id);
        $department = Department::query()->where('code', $departmentCode)->firstOrFail();
        $user->departments()->sync([$department->id => ['is_primary' => true]]);
        $user->load('roles');

        return [$user, $department];
    }
}
