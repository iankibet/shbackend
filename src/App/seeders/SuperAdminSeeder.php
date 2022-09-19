<?php

namespace Database\Seeders;

use App\Models\Core\Department;
use App\Models\User;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $departmentName = 'Seeded Super Admin';
        $department = Department::updateOrCreate([
            'name'=>$departmentName,
        ],
            [
                'name'=>$departmentName,
                'description'=>'Seeder at: '.time()
            ]);

        $user = User::updateOrCreate([
            'email'=>'seed@localhost.com'
        ],[
            'name'=>'Seed Admin',
            'role'=>'admin',
            'email'=>'seed@localhost.com',
            'password'=>Hash::make(12345678910)
        ]);
        $adminPermissions = RoleRepository::getRolePermissions('admin',true);
        foreach ($adminPermissions as $module){
            $permissions  = RoleRepository::getModulePermissions('admin',$module);
            $realPermissions = $permissions;
            if($permissions){
                $departmentPermission = $department->permissions()->updateOrCreate([
                    'module'=>$module
                ],[
                        'module'=>$module,
                        'permissions'=>json_encode(array_values($permissions))
                    ]
                );
            }
        }
        $user->department_id = $department->id;
        $user->update();
    }
}
