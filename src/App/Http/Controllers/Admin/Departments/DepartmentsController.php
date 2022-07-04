<?php

namespace App\Http\Controllers\Api\Admin\Departments;

use App\Http\Controllers\Controller;
use App\Repositories\RoleRepository;
use Illuminate\Http\Request;

use App\Models\Core\Department;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class DepartmentsController extends Controller
{

     public function __construct()
        {
            $this->api_model = Department::class;
        }
        public function storeDepartment($id=0){
            $data = \request()->all();
            $valid = Validator::make($data,$this->getValidationFields());
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $data['form_model'] = encrypt($this->api_model);
            // $data['user_id'] = \request()->user()->id;
            $data['id'] = $id;
            $department = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'department'=>$department
            ];
        }

        public function listDepartments(){
            $user = \request()->user();
            $departments = new Department();
            $table = 'departments';
            $search_keys = array_keys($this->getValidationFields());
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
            return [
                 'status'=>'success',
                 'department'=>$department
            ];
        }

        public function getDepartment($id){
            $user = \request()->user();
    //        $department = Department::find($id);
            $department = Department::where('user_id',$user->id)->find($id);
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
