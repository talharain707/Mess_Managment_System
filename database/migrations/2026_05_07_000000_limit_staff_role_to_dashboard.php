<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $staffRole = Role::where('name', 'staff')->first();

        if ($staffRole) {
            $staffRole->syncPermissions([
                Permission::firstOrCreate([
                    'name' => 'view dashboard',
                    'guard_name' => 'web',
                ]),
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $staffRole = Role::where('name', 'staff')->first();

        if ($staffRole) {
            $staffRole->syncPermissions([
                Permission::firstOrCreate([
                    'name' => 'view dashboard',
                    'guard_name' => 'web',
                ]),
                Permission::firstOrCreate([
                    'name' => 'manage expenses',
                    'guard_name' => 'web',
                ]),
                Permission::firstOrCreate([
                    'name' => 'manage payments',
                    'guard_name' => 'web',
                ]),
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
