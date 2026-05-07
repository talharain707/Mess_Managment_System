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

        $managerRole = Role::where('name', 'manager')->first();

        if ($managerRole) {
            $managerRole->syncPermissions([
                Permission::firstOrCreate([
                    'name' => 'view dashboard',
                    'guard_name' => 'web',
                ]),
                Permission::firstOrCreate([
                    'name' => 'manage expenses',
                    'guard_name' => 'web',
                ]),
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $managerRole = Role::where('name', 'manager')->first();

        if ($managerRole) {
            $managerRole->syncPermissions(Permission::query()->get());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
