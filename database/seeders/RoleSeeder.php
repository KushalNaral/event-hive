<?php

// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $nonAdminRole = Role::firstOrCreate(['name' => 'non-admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $tempAdminUserId = env('TEMP_ADMIN_USER_ID');

        if ($tempAdminUserId) {
            $user = User::find($tempAdminUserId);

            if ($user) {
                $user->roles()->detach();  // Detach any current role

                $user->roles()->attach($adminRole->id);
                echo "Admin role attached to user with ID: {$tempAdminUserId}\n";
            } else {
                echo "User with ID: {$tempAdminUserId} not found.\n";
            }
        } else {
            echo "TEMP_ADMIN_USER_ID not set in .env file.\n";
        }
    }
}

