<?php

namespace App\Http\Controllers\Api\Admin\Departments\Department;

use App\Http\Controllers\Controller;
use App\Models\Core\Department;
use App\Models\Core\DepartmentPermission;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Couchbase\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function listModules($department_id){
        $modules = DepartmentPermission::where('department_id',$department_id);
        return [
            'status'=>'success',
            'data'=>SearchRepo::of($modules)
            ->make(true)
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
        return [
            'module'=>$module,
            'permissions'=>$adminPermissions
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
}
