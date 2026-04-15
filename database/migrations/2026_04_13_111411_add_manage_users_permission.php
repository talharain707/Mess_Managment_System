<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'manage users',
            'guard_name' => 'web'
        ]);

        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($perm);
        }
    }

    public function down()
    {
        \Spatie\Permission\Models\Permission::where('name', 'manage users')->delete();
    }
};
