<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Http\Controllers\Controller;
use App\Jobs\ReUpdateAffiliateReports;
use App\Jobs\UpdateAgentListings;
use App\Models\Core\ApprovedAccount;
use App\Models\Core\FreeTier;
use App\Models\Core\HouseRequest;
use App\Models\Core\Log;
use App\Models\Core\Property;
use App\Models\Core\TmpFile;
use App\Models\Core\UploadedDocument;
use App\Repositories\StatusRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Models\User;
use App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

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
                  storeLog('user_activation',"$login_user->role  <a target='_blank' href='/admin/users/user/$login_user->id'>  $login_user->name</a> activated <a target='_blank' href='/admin/users/user/$user->id'>$user->name#$user->id</a>",$user);
                  $user->sendNotification($user->id,"notify_".$user->role."_account_activated");
               } else {
                  storeLog('user_activation',"$login_user->role  <a target='_blank' href='/admin/users/user/$login_user->id'>  $login_user->name</a> deactivated <a target='_blank' href='/admin/users/user/$user->id'>$user->name#$user->id</a>",$user);
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
