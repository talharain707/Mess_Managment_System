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

        $permission = Permission::firstOrCreate([
            'name' => 'manage users',
            'guard_name' => 'web',
        ]);

        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
