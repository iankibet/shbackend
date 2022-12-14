<?php

namespace Iankibet\Shbackend\App\Http\FrameworkControllers\Sh;

use App\Http\Controllers\Controller;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Iankibet\Shbackend\App\Repositories\ShRepository;
use Illuminate\Http\Request;

use App\Models\Core\Department;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class ShDepartments extends Controller
{

    public function __construct()
    {
        $this->api_model = Department::class;
    }
    public function storeDepartment($id=0){
        $data = \request()->all();
        $valid = Validator::make($data,ShRepository::getValidationFields($this->api_model));
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $data['form_model'] = encrypt($this->api_model);
        $department = $this->autoSaveModel($data);
        if($id){
            $data['id'] = $id;
        }
        if(isset($data['id']) && $data['id'] > 0) {
            ShRepository::storeLog('updated_department',"Updated department # $department->id $department->name", $department);
        } else {
            ShRepository::storeLog("created_department","Created new department #$department->id $department->name",$department);
        }
        return [
            'status'=>'success',
            'department'=>$department
        ];
    }

    public function listDepartments(){
        $user = \request()->user();
        $departments = new Department();
        $table = 'departments';
        $search_keys = array_keys(ShRepository::getValidationFields($this->api_model));
        return[
            'status'=>'success',
            'data'=>SearchRepo::of($departments,$table,$search_keys)
                ->make(true)
        ];
    }

    public function updatePermissions($id){
        $department = Department::find($id);
        $department->permissions = request('permissions');
        $department->save();
        ShRepository::storeLog("update_department_permissions", "Updated department permissions for department#$department->id $department->name",$department);
        return [
            'status'=>'success',
            'department'=>$department
        ];
    }

    public function getDepartment($id){
        $user = \request()->user();
        $department = Department::find($id);
        return [
            'status'=>'success',
            'department'=>$department
        ];
    }
    public function deleteDepartment($id){
        $user = \request()->user();
        //        $department = Department::find($id);
        $department = Department::where('user_id',$user->id)->find($id);
        $department->delete();
        return [
            'status'=>'success',
        ];
    }

    public function allPermissions(){
        $adminPermissions = RoleRepository::getRolePermissions('admin');
        return $adminPermissions;
    }

}
