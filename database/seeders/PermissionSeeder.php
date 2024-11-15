<?php

// database/seeders/PermissionSeeder.php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Admin Permissions
            'assign-role-to-user',
            'publish-event',
            'verify-event',
            'remove-role-from-user',
            'assign-permission-to-role',
            'remove-permission-from-role',

            // Auth Permissions
            'register',
            'login',
            'view-profile',

            // Event Category Permissions
            'view-event-categories',
            'create-event-category',
            'update-event-category',
            'delete-event-category',
            'assign-category-to-users',

            // Event Permissions
            'view-all-events',
            'view-event-details',
            'create-event',
            'update-event',
            'delete-event',
            'view-recommendations',
            'view-attending-events',
            'view-attended-events',
            'view-bookmarked-events',
            'view-liked-events',

            // User Interaction Permissions
            'view-default-interactions',
            'set-interactions',
            'view-all-interactions',
            'view-user-interactions',
            'view-event-interactions',

            // Event Ratings Permissions
            'view-event-ratings',
            'create-event-rating',

            // User Permissions
            'update-user-preferences',
            'view-user-events',

            // OTP Permissions
            'verify-otp',
            'resend-otp',
        ];

        $permissionModels = [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissionModels[] = $permission;
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $adminRole->permissions()->sync(Permission::all());

        echo "Permissions seeded successfully.\n";
    }
}

