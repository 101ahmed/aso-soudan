<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = getenv('ADMIN_EMAIL') ?: 'admin@acs-rennes.fr';
        $password = getenv('ADMIN_PASSWORD') ?: 'Password123!';

        if (trim((string) $email) === '') {
            $email = 'admin@acs-rennes.fr';
        }
        if (trim((string) $password) === '') {
            $password = 'Password123!';
        }

        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'phone' => null,
            'locale' => 'fr',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->password = $password;
        $user->save();

        User::query()
            ->where('email', 'admin@rabta.local')
            ->where('id', '!=', $user->id)
            ->update(['email' => 'admin@acs-rennes.fr.migrated']);

        $role = Role::query()->where('code', 'SUPER_ADMIN')->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        if (! Hash::check($password, $user->fresh()->password)) {
            throw new \RuntimeException('SuperAdminSeeder: password hash verification failed.');
        }
    }
}
