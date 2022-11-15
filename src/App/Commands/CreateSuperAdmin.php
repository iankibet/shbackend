<?php

namespace Iankibet\Shbackend\App\Commands;

use App\Models\Core\Department;
use App\Models\Core\DepartmentPermission;
use App\Models\User;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sh:add-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->ask('Name', 'Super Admin');
        $email = $this->ask('Admin Email', 'admin@localhost.com');
        $password = $this->ask('Admin Password', 'admin@localhost.com');
        $department_id = 0;
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password)
        ]);
        if (strtolower($this->ask('Create Department?, y/n', 'y')) == 'y') {
            $department = $this->ask('Department Name?', 'Super Admin');
            $department = Department::create([
                'name' => $department,
                'description' => $department
            ]);
            $adminPermissions = RoleRepository::getRolePermissions('admin', true);
            foreach ($adminPermissions as $module) {
                $permissions = RoleRepository::getModulePermissions('admin', $module);
                $realPermissions = $permissions;
                if ($permissions) {
                    $departmentPermission = $department->permissions()->updateOrCreate([
                        'module' => $module
                    ], [
                            'module' => $module,
                            'permissions' => json_encode(array_values($permissions))
                        ]
                    );
                }
            }
            $user->role = 'admin';
            $user->department_id = $department->id;
            $user->update();
            return 0;
        }
    }
}
