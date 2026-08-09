<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@acs-rennes.fr'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone' => null,
                'locale' => 'fr',
                'status' => 'active',
                'password' => 'Password123!',
                'email_verified_at' => now(),
            ]
        );

        // Migration depuis l'ancien email de dev (.local rejeté par certains navigateurs)
        User::query()
            ->where('email', 'admin@rabta.local')
            ->where('id', '!=', $user->id)
            ->update(['email' => 'admin@acs-rennes.fr.migrated']);

        $role = Role::query()->where('code', 'SUPER_ADMIN')->first();

        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
