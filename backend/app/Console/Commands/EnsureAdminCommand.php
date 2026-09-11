<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdminCommand extends Command
{
    protected $signature = 'rdp:ensure-admin
                            {--email= : Admin email}
                            {--password= : Admin password}';

    protected $description = 'Ensure Super Admin, roles and departments exist (does not reset an existing password)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => RolePermissionSeeder::class,
            '--force' => true,
        ]);
        $this->call('db:seed', [
            '--class' => DepartmentSeeder::class,
            '--force' => true,
        ]);
        $this->call('db:seed', [
            '--class' => \Database\Seeders\ShuraMemberSeeder::class,
            '--force' => true,
        ]);
        $this->call('db:seed', [
            '--class' => \Database\Seeders\ParentsCouncilMemberSeeder::class,
            '--force' => true,
        ]);
        $this->call('db:seed', [
            '--class' => \Database\Seeders\AcademicAttendanceSeeder::class,
            '--force' => true,
        ]);

        $email = $this->option('email')
            ?: (getenv('ADMIN_EMAIL') ?: null)
            ?: 'admin@acs-rennes.fr';
        $providedPassword = $this->option('password') ?: (getenv('ADMIN_PASSWORD') ?: null);

        if (trim((string) $email) === '') {
            $email = 'admin@acs-rennes.fr';
        }
        if (is_string($providedPassword) && trim($providedPassword) === '') {
            $providedPassword = null;
        }

        /** @var User $user */
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $isNew = ! $user->exists;

        if ($user->trashed()) {
            $user->restore();
            $isNew = false;
        }

        $user->fill([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'locale' => 'fr',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        if ($isNew) {
            $password = $providedPassword;
            if (! $password) {
                if (app()->environment('production')) {
                    $this->error('ADMIN_PASSWORD must be set in the environment to create the first admin.');

                    return self::FAILURE;
                }
                $password = 'Password123!';
            }
            $user->password = $password;
            $user->save();
            $user->refresh();
            if (! Hash::check($password, $user->password)) {
                $this->error('Password hash verification failed after save.');

                return self::FAILURE;
            }
        } else {
            $user->save();
        }

        $role = Role::query()->where('code', 'SUPER_ADMIN')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }

        $this->info("Admin ready: {$email}");

        return self::SUCCESS;
    }
}
