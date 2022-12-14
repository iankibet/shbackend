<?php

namespace  Iankibet\Shbackend\App\Http\FrameworkControllers\Sh\Department;

use App\Http\Controllers\Controller;
use App\Models\Core\Department;
use App\Models\Core\DepartmentPermission;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Couchbase\Role;
use Iankibet\Shbackend\App\Repositories\ShRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShDepartmentController extends Controller
{
    public function listModules($department_id){
        $modules = DepartmentPermission::where('department_id',$department_id);
        return [
            'status'=>'success',
            'data'=>SearchRepo::of($modules)
                ->make(true)
        ];
    }
    public function listAllModules($role,$id){
        $department = Department::find($id);
        $modules = RoleRepository::getRolePermissions($role,true);
        $commonIndex = array_search('common',$modules,true);
        if($commonIndex !== false){
            unset($modules[$commonIndex]);
        }
        $modules = array_values($modules);
        $modules = array_unique($modules);
        return [
            'modules'=>$modules,
            'department'=>$department,
            'departmentModules'=>$department->permissions()->pluck('module')->toArray()
        ];
    }
    public function listPendingModules($department_id){
        $modules = DepartmentPermission::where('department_id',$department_id)->pluck('module')->toArray();
        $adminPermissions = RoleRepository::getRolePermissions('admin',true);
        $new = [];
        foreach ($adminPermissions as $module){
            if(in_array($module,$modules))
                continue;
            $new[] = [
                'id'=>$module,
                'name'=>ucwords(str_replace('_',' ',$module))
            ];
        }
        return [
            'status'=>'success',
            'data'=>$new
        ];
    }
    public function addModule($department_id){
        $data = \request()->all();
        $rules = [
            'permission_module'=>'required'
        ];
        $valid = Validator::make($data,$rules);
        if($valid->errors()->count()){
            return response([
                'status'=>'failed',
                'errors'=>$valid->errors()
            ], 422);
        }
        $module = DepartmentPermission::updateOrCreate([
            'department_id'=>$department_id,
            'module'=>$data['permission_module']
        ],[
            'department_id'=>$department_id,
            'module'=>$data['permission_module']
        ]);
        $module = DepartmentPermission::find($module->id);
        if(!$module->permissions){
            $module->permissions = [$module->module];
        }
        $roleRepo = new RoleRepository();
        $allowed_urls = $roleRepo->extractRoleUrls($module->module,$module->permissions,'admin');
        $module->urls = $allowed_urls;
        session()->put('permissions',null);
        session()->put('allowed_urls',null);
        return response([
            'status'=>'success',
            'module'=>$module
        ]);
    }

    public function getModule($module_id){
        $module = DepartmentPermission::find($module_id);
        if($module->permissions){
            $module->permissions = json_decode($module->permissions);
        }
        return [
            'module'=>$module
        ];
    }
    public function getModulePermissions($module){
        $adminPermissions = RoleRepository::getModulePermissions('admin',$module);
        $selectedPermissions = [];
        if(\request('department_id')){
            $selectedPermissions = @DepartmentPermission::where([
                ['department_id','=',\request('department_id')],
                ['module','=',$module]
            ])->first()->permissions;
//            dd($selectedPermissions);
            if($selectedPermissions){
                $selectedPermissions = json_decode($selectedPermissions);
//                $replaced = [];
//                foreach ($selectedPermissions as $permission){
//                    if($permission != $module){
//                        $replaced[] = $permission;
//                    }
//                }
//                $selectedPermissions = $replaced;
            }
        }
        return [
            'module'=>$module,
            'permissions'=>$adminPermissions,
            'selectedPermissions'=>$selectedPermissions
        ];
    }

    public function setModulePermissions($id){
        $module = DepartmentPermission::find($id);
        $permissions = \request('permissions');
        $permissions[] = $module->module;
        $module->permissions = $permissions;
        $roleRepo = new RoleRepository();
        $allowed_urls = $roleRepo->extractRoleUrls($module->module,$module->permissions,'admin');
        $module->urls = $allowed_urls;
        $module->update();
        session()->put('permissions',null);
        session()->put('allowed_urls',null);
        return [
            'status'=>'success',
            'module'=>$module
        ];
    }
    public function updateModulePermissionsWithSlug($id,$module){
        $department = Department::findOrFail($id);
        $module = $department->permissions()->where('module',$module)->firstOrCreate([
            'module'=>$module
        ]);
        $permissions = \request('permissions');
        if(!count($permissions)){
            $module->delete();
        } else {
            $module->permissions = $permissions;
            $roleRepo = new RoleRepository();
            $allowed_urls = $roleRepo->extractRoleUrls($module->module,$module->permissions,'admin');
            $module->urls = $allowed_urls;
            $module->update();
        }
        return [
            'status'=>'success',
            'module'=>$module,
            'departmentModules'=>$department->permissions()->pluck('module')->toArray()
        ];
    }
    public function removeModulePermissions($id)
    {
        $moduleDepartment = DepartmentPermission::find($id);
        $department = Department::find($moduleDepartment->department_id);
        $moduleDepartment->delete();
        ShRepository::storeLog('remove_department_permission',"Removed permission $moduleDepartment->module from Department#$department->id", $department);
        return response([
            'status' => 'success'
        ]);

    }
}
