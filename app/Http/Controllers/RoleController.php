<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function assignRoleToUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $role = Role::where('name', $request->role)->firstOrFail();

            // Remove any existing roles before assigning the new role
            $user->roles()->detach(); // Detach previous roles
            $user->assignRole($role->id); // Assign the new role

            return successResponse(
                $user->roles,
                "Role '{$role->name}' assigned to user successfully."
            );

        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors()
            );
        }
    }

    public function removeRoleFromUser(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);
            $role = Role::where('name', $request->role)->firstOrFail();

            // Remove the role from the user
            $user->removeRole($role->id);

            return successResponse(
                $user->roles,
                "Role '{$role->name}' removed from user successfully."
            );

        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors() ?? []
            );
        }
    }

    public function updatePermissionsForRole(Request $request, $roleId)
    {
        try {
            // Validate the incoming request to ensure 'permissions' is an array
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,name', // Ensure each permission name exists
            ]);

            // Find the role by ID
            $role = Role::findOrFail($roleId);

            // Fetch the permissions based on the names sent in the request
            $permissions = Permission::whereIn('name', $request->permissions)->get();

            // Attach new permissions to the role
            $role->permissions()->syncWithoutDetaching($permissions->pluck('id'));

            // You can optionally detach permissions that are no longer in the list
            // $role->permissions()->sync($permissions->pluck('id'));

            return successResponse(
                $role->permissions,
                "Permissions updated successfully."
            );

        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th->getMessage()
            );
        }
    }

    public function getAllRoles(){
        try {
            $roles = Role::distinct('name')->get();
            return successResponse($roles, 'All roles retrieved succesfully.');
        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors() ?? []
            );
        }
    }

    public function getAllPermissions(){
        try {
            $roles = Permission::distinct('name')->get()->pluck('name')->toArray();
            return successResponse($roles, 'All roles retrieved succesfully.');
        } catch (\Throwable $th) {
            return errorResponse(
                $th->getMessage(),
                $th->getCode() ?: 500,
                $th?->errors() ?? []
            );
        }
    }
}
