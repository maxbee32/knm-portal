<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;



class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'create appointment','guard_name'=>'admin-api']);
        Permission::create(['name' => 'check ticket','guard_name'=>'admin-api']);

        $role = Role::create(['name' => 'reservation admin', 'guard_name'=>'admin-api']);
        $role->givePermissionTo('create appointment');


        $role = Role::create(['name' => 'ticket admin','guard_name'=>'admin-api']);
        $role->givePermissionTo('check ticket');

    }
}
