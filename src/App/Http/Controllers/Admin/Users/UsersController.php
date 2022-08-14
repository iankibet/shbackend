<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\ReUpdateAffiliateReports;
use App\Models\Core\ApprovedAccount;
use App\Models\Core\FreeTier;
use App\Models\Core\HouseRequest;
use App\Models\Core\Log;
use App\Models\Core\Property;
use App\Models\Core\TmpFile;
use App\Models\Core\UploadedDocument;
use App\Models\User;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Carbon\Carbon;
use Iankibet\Shbackend\App\Repositories\ShRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
                'phone'=>'required|unique:users'
            ];
            if(request('email') != null) {
                $rules['email'] = 'required|unique:users';
            }
            if(request('id_number') != null) {
                $rules['email'] = 'required|unique:users';
            }
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
            if(!isset($data['password'])){
                $data['password'] = Str::random(12);
            }
            if(!isset($data['email'])){
                $data['email'] = $data['phone'];
            }
            // $data['user_id'] = \request()->user()->id;
            if(request('role')){
                $data['role'] = 'member';
            }
            $user = $this->autoSaveModel($data);
            if(request('id')) {
                ShRepository::storeLog('updated_member', "Updated details of member#$user->id $user->name", $user);
            } else {
                ShRepository::storeLog('added_new_member', "Added member#$user->id $user->name", $user);
            }
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
            ShRepository::storeLog('update_user_details',"Updated user#$user->id $user->name details",$user);
            return [
              'status'=>'success',
              'user'=>$user
            ];
        }
        public function updatePassword($id) {
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

        public function listAdminUsers(){
            $user = \request()->user();
            $users = User::where('users.role','like','admin')
                ->leftJoin('departments','departments.id','=','users.department_id')
                ->select('users.*','departments.name as department');
            $table = 'users';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($users,$table,$search_keys)
                    ->make(true)
            ];
        }
        public function listMemberUsers(){
            $user = \request()->user();
            $users = User::where('users.role','like','member')
                ->select('users.*');
            $table = 'users';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($users,$table,$search_keys)
                    ->make(true)
            ];
        }

        public function getUser($id){
            $user = User::find($id);
            $referrer = '';
            return [
                'status'=>'success',
                'user'=>$user
            ];
        }
          public function updateStatus($id){
         $login_user = \request()->user();
            $ActiveFreeTier= FreeTier::where('status',StatusRepository::getFreeTierStatus('active'))->first();
            $user = User::find($id);
            if ( $user->status == 0 && $user->role == 'agent'){
                //set date approved
                if($user->free_tier_id ===null) {
                    if ($ActiveFreeTier) {
                        $days = $ActiveFreeTier['days'];
                        if (is_numeric($days)) {
                            $days = $days * 1;
                        }
                        $tier_id = $ActiveFreeTier['id'];
                        $user->status = $user->status = 1;
                        $user->free_tier_id = $tier_id;
                        $user->free_tier_deadline = Carbon::now()->addDays($days);
                        $properties =  $user->properties()->get();
                        $userDetails = new Carbon($user->free_tier_deadline);
                        $now = Carbon::now();
                        if ($properties) {
                            foreach ($properties as $property) {
                                $property->paid_until = $userDetails;
                                $property->paid_at = $now;
                                $property->payment_period = 'trial';
                                $property->update();
                            }
                        }
                    }
                }
                $approved = ApprovedAccount::where('user_id',$user->id)->first();
                if(!$approved){
                    ApprovedAccount::create([
                       'user_id'=>$user->id,
                       'approved_by'=>\request()->user()->id,
                       'approved_at'=>now()
                    ]);
                    if($user->referrer_id){
                        $referrer = User::find($user->referrer_id);
                        ReUpdateAffiliateReports::dispatch($referrer);
                    }
                }
              else {
                    $user->status = $user->status = 1;
                }
            }else {
                $user->status = $user->status === 0 ? 1:0;
            }
            $user->update();
              if ($user->status == 1) {
                  ShRepository::storeLog('user_activation',"$login_user->role  <a target='_blank' href='/admin/users/user/$login_user->id'>  $login_user->name</a> activated <a target='_blank' href='/admin/users/user/$user->id'>$user->name#$user->id</a>",$user);
                  $user->sendNotification($user->id,"notify_".$user->role."_account_activated");
               } else {
                  ShRepository::storeLog('user_activation',"$login_user->role  <a target='_blank' href='/admin/users/user/$login_user->id'>  $login_user->name</a> deactivated <a target='_blank' href='/admin/users/user/$user->id'>$user->name#$user->id</a>",$user);
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
        public function listUsers($role){
         $users = User::where('role',$role);
            $search_keys = array_keys(['name','email','phone']);
//            $search_keys[] = 'users.name';
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($users,'logs',$search_keys)
                    ->make(true)
            ];
        }


}
