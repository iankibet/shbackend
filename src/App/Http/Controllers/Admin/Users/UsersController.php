<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Jobs\ReUpdateAffiliateReports;
use App\Models\Core\ApprovedAccount;
use App\Models\Core\FreeTier;
use App\Models\Core\HouseRequest;
use App\Models\Core\Log;
use App\Models\Core\Property;
use App\Models\Core\TmpFile;
use App\Models\Core\UploadedDocument;
use App\Models\User;
use App\Repositories\SearchRepo;
use Carbon\Carbon;
=======
use App\Models\Core\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\User;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
>>>>>>> main
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Shara\Framework\App\Repositories\StatusRepository;

class UsersController extends Controller
{

     public function __construct()
        {
            $this->api_model = User::class;
        }
        public function storeUser(){
            $data = \request()->all();
            $rules = [
                'name'=>'required',
                'department_id'=>'required|integer',
                'email'=>'required|unique:users',
                'password'=>'required|confirmed',
                'phone'=>'required'
            ];
            if(@$data['id']){
                $rules = [
                    'name'=>'required',
                    'department_id'=>'required|integer',
                    'email'=>'required',
                    'phone'=>'required'
                ];
                $user = User::findOrFail($data['id']);
                if($user->email != $data['email']){
                    $rules['email'] = 'required|unique:users';
                }
            }
            $valid = Validator::make($data,$rules);
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $data['form_model'] = encrypt($this->api_model);
            // $data['user_id'] = \request()->user()->id;
            $data['role'] = 'admin';
            $user = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'user'=>$user
            ];
        }
        public function updateUser(){
            $data = \request()->all();
            if(@$data['id']){
                $rules = [
                    'name'=>'required',
                    'email'=>'required',
                    'phone'=>'required'
                ];
                $user = User::findOrFail($data['id']);
                if($user->email != $data['email']){
                    $rules['email'] = 'required|unique:users';
                }
            }
            $valid = Validator::make($data,$rules);
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $data['form_model'] = encrypt($this->api_model);
            // $data['user_id'] = \request()->user()->id;
            $user = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'user'=>$user
            ];
        }
        public function Updatepassord($id) {
            $data = \request()->all();
            $valid= Validator::make($data,[
                'password'=>'required|confirmed',
            ]);
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $user = User::findOrFail($id);
            $user->password = Hash::make(\request('password'));
            $user->update();
            return [
                'status'=>'success',
                'message'=>'Password updated'
            ];
        }

        public function listUsers($role){
            $user = \request()->user();
            $users = User::where('users.role','like',$role)
                ->leftJoin('departments','departments.id','=','users.department_id')
                ->select('users.*','departments.name as department');
            $table = 'users';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($users,$table,$search_keys)
                    ->addColumn('houses',function($user){
                        if ($user->role === 'agent') {
                            $str ='';
                            $count_rent =  $user->properties()->where('submission_type','rent')->count();
                            if ($count_rent > 0 ) {
                                $str  .= 'Rent ';
                                $str  .= '<a target="_blank" href="/admin/users/user/'.$user->id.'/tab/property_listings">'.$count_rent.'</a>';
                            }
                            $count_sale =  $user->properties()->where('submission_type','sale')->count();
                            if ($count_sale > 0 ) {
                                $str .= ' | ';
                                $str  .= 'Sale ';
                                $str  .= '<a target="_blank" href="/admin/users/user/'.$user->id.'/tab/property_listings">'.$count_sale.'</a>';
                            } else {
                                $str .= 'No listing';
                            }
                            return $str;
                        }
                    })
                    ->make(true)
            ];
        }

        public function getUser($id){
            $user = \request()->user();
    //        $user = User::find($id);
            $user = User::find($id);
            $referrer = '';
            if($user->referrer_id){
                $referrer = User::find($user->referrer_id);
                if($referrer){
                    $referrer = $referrer->name;
                }
            }
            $user->referrer = $referrer;
            if($user->role === 'agent'){
                $config = $user->agentConfig;
                if(!$config){
                    $config = $user->agentConfig()->create([
                        'current_step'=>1
                    ]);
                }
                $user->agentConfig = json_decode($config->allowed_notifications);
                $user->location_type = $config->location_type;
                $user->allowed_notification_type = $config->allowed_notification_type;
                $user->locations = json_decode($config->locations);
                $tier_deadline = new Carbon($user->free_tier_deadline);
                if($tier_deadline->isFuture()){
                    $user->free_days = $tier_deadline->diffInDays();
                }
            }
            return [
                'status'=>'success',
                'user'=>$user
            ];
        }
        public function listUserLogs($id){
         $logs = Log::where('user_id',$id);
            $search_keys = array_keys(['slug','description']);
            $search_keys[] = 'users.name';
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($logs,'logs',$search_keys)
                    ->make(true)
            ];
        }


}
