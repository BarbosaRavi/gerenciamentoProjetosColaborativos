<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsAndRoles extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'teams.view',
            'teams.create',
            'teams.update',
            'teams.delete',
            'teams.invite',
            'teams.kick',

            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.invite',
            'projects.kick',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',

            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',

            'tags.view',
            'tags.create',
            'tags.update',
            'tags.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $adminRole->syncPermissions(
            Permission::where('guard_name', 'api')->pluck('name')->all()
        );

        $userRole->syncPermissions([
            'teams.view',
            'teams.create',
            'projects.view',
            'projects.create',
            'tasks.view',
            'tasks.create',
            'comments.view',
            'comments.create',
            'tags.view',
            'tags.create',
        ]);
    }
}