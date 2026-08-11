<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminCommand extends Command
{
    protected $signature = 'rdp:ensure-admin
                            {--email= : Admin email}
                            {--password= : Admin password}';

    protected $description = 'Ensure Super Admin exists with a known password (idempotent)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => RolePermissionSeeder::class,
            '--force' => true,
        ]);

        $email = $this->option('email')
            ?: (getenv('ADMIN_EMAIL') ?: null)
            ?: 'admin@acs-rennes.fr';
        $password = $this->option('password')
            ?: (getenv('ADMIN_PASSWORD') ?: null)
            ?: 'Password123!';

        if (trim((string) $email) === '') {
            $email = 'admin@acs-rennes.fr';
        }
        if (trim((string) $password) === '') {
            $password = 'Password123!';
        }

        /** @var User $user */
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->fill([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'locale' => 'fr',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Plain password — User cast "hashed" will bcrypt once
        $user->password = $password;
        $user->save();

        // Verify round-trip so deploy logs catch hash problems early
        $user->refresh();
        if (! Hash::check($password, $user->password)) {
            $this->error('Password hash verification failed after save.');

            return self::FAILURE;
        }

        $role = Role::query()->where('code', 'SUPER_ADMIN')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $this->info("Admin ready: {$email}");

        return self::SUCCESS;
    }
}
