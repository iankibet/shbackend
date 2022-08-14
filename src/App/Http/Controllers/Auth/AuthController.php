<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Core\AgentListing;
use App\Models\Core\Company;
use App\Models\Core\DepartmentPermission;
use App\Models\Core\HouseRequest;
use App\Models\Core\InterestedRequest;
use App\Models\Core\Property;
use App\Models\Core\RequiredDocument;
use App\Models\Core\UploadedDocument;
use App\Models\User;
use App\Notifications\InviteMember;
use App\Notifications\SendSms;
use App\Notifications\verifyPhone;
use Iankibet\Shbackend\App\Repositories\RoleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Iankibet\Shbackend\App\Repositories\ShRepository;

class AuthController extends Controller
{
    //
    public function login(){
        $data = \request()->all();
        $valid = Validator::make($data,[
            'email'=>'required',
            'password'=>'required'
        ]);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $email = \request('email');
        $password = \request('password');
        if(Auth::attempt(['email'=>$email,'password'=>$password])){
            $token = request()->user()->createToken('api_token_at_'.now()->toDateTimeString());
            $user= \request()->user();
            ShRepository::storeLog('user_login',"$user->role($user->name) logged in",$user);
            return [
                'status'=>'success',
                'token'=>$token->plainTextToken,
                'user'=>request()->user()
            ];
        }
        return response([
            'status'=>'failed',
            'errors'=>['email'=>['Invalid email or password']]
        ],422);
    }

    public function forgotPassword(Request $request){
        $data = \request()->all();
        $valid = Validator::make($data,[
            'email'=>'required|email',
        ]);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $status = Password::sendResetLink(
            $request->only('email')
        );
        if($status !== Password::RESET_LINK_SENT){
            return response([
                'status' => 'failed',
                'errors' => ['email'=> [__($status)]]
            ], 422);
        }
        return [
            'status'=>'success',
            'message'=>'Email sent'
        ];
    }
    public function resetPassword(Request $request){
        $data = \request()->all();
        $valid = Validator::make($data,[
            'email'=>'required|email',
            'new_password'=>'required|confirmed'
        ]);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $credentials =  $request->only('email', 'token');
        if (is_null($user = $this->broker()->getUser($credentials))) {
            return response([
                'status' => 'failed',
                'errors' => ['email'=>[trans(Password::INVALID_USER)]]
            ], 422);
        }
        if (! $this->broker()->tokenExists($user, $credentials['token'])) {
            return response([
                'status' => 'failed',
                'errors' => ['email'=>[trans(Password::INVALID_TOKEN)]]
            ], 422);
        }
        $user->password = Hash::make(\request('new_password'));
        $user->update();
        return [
            'status'=>'success',
            'message'=>'Password updated'
        ];
    }
    public function broker()
    {
        return Password::broker();
    }
    public function register(){
        $rules = [
            'email'=>'required|unique:users',
            'name'=>'required',
            'phone'=>'required',
            'password'=>'required|confirmed',
        ];
        $data = \request()->all();
        $valid = Validator::make($data,$rules);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $email = \request('email');
        $password = \request('password');
        $name = \request('name');
        $phone = \request('phone');
        $role = 'client';
        $user = User::create([
            'name'=>$name,
            'email'=>$email,
            'phone'=>$phone,
            'role'=>$role,
            'password'=>Hash::make($password)
        ]);
        $token = $user->createToken('api_token_at_'.now()->toDateTimeString());
        ShRepository::storeLog('user_registration',"$user->role  <a target='_blank' href='/admin/users/user/$user->id'> $user->name</a> registered",$user);
        return [
            'status'=>'success',
            'user'=>$user,
            'token'=>$token->plainTextToken
        ];
    }
    public function getUser(){
        $user = request()->user();
        if($user->role == 'admin'  && $user->department_id){
            $permissions = [];
            $modules = DepartmentPermission::where('department_id',$user->department_id)->get();
            foreach ($modules as $module){
                $mainModule = $module->module;
                $permissions[] = $mainModule;
                $modulePermissions = json_decode($module->permissions);
                if($modulePermissions){
                    foreach ($modulePermissions as $modulePermission){
                        if($modulePermission != $mainModule){
                            $permissions[] = $mainModule.'.'.$modulePermission;
                        }
                    }
                }
            }
            $user->permissions = json_encode($permissions);

        } elseif($user->role != 'admin'){
            $permissions = RoleRepository::getRolePermissions($user->role);
            $user->permissions = json_encode($permissions);
        }
        $menuCounts = [
            'slug'=>0//slug as key then count as integer
        ];
        $user->menuCounts = $menuCounts;
        return $user;
    }
    public function getRandomString($len=2){
        $str = '';
        $a = "abcdefghijklmnopqrstuvwxyz";
        $b = str_split($a);
        for ($i=1; $i <= $len ; $i++) {
            $str .= $b[rand(0,strlen($a)-1)];
        }
        return $str;
    }
    public function updateProfile(){
        $user = \request()->user();
        $data = \request()->all();
        if($user->role == 'client') {
            $valid = Validator::make($data,[
                'phone'=>'required',
                'name'=>'required'
            ]);
        } else {
            $valid = Validator::make($data,[
                'phone'=>'required',
                'name'=>'required',
                'city'=>'required',
                'street'=>'required'
            ]);
        }
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $phone = request('phone');
        $phone_arr = explode(':',$phone);
        $previous_phone = $user->phone;
        if(count($phone_arr) == 3) {
            $phone = $phone_arr[2];
            if(!$phone){
                return response([
                    'status' => 'failed',
                    'errors' => ['phone'=>['phone is required']]
                ], 422);
            }
            $country_code = $phone_arr[0];
            $ext = $phone_arr[1];
            $phone = $ext.$phone;
            $user->phone = $phone;
            $user->country_code = $country_code;
        } else {
            $user->phone = \request('phone');
        }
        if($previous_phone != $phone){
            $user->otp=null;
            $user->phone_verified_at = null;
        }
        if(strlen($user->phone)<10){
            return response([
                'status' => 'failed',
                'errors' => ['phone'=>['Invalid phone number provided']]
            ], 422);
        }
        $user->name = \request('name');
        if(\request('city')){
            $user->city = \request('city');
            $user->street = \request('street');
        }
        if($user->role == 'agent'){
            $user->profile_step = 2.1;
        }
        $user->update();
        return [
            'status'=>'success',
            'user'=>$user
        ];
    }
    public function updatePassword(){
        $data = \request()->all();
        $valid = Validator::make($data,[
            'current_password'=>'required',
            'new_password'=>'required|confirmed',
        ]);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        $user = \request()->user();
        if(!Hash::check(\request('current_password'),$user->password)){
            return response([
                'status' => 'failed',
                'errors' => ['current_password'=>['Current password incorrect']]
            ], 422);
        }
        $new_password = request('new_password');
        $user->password = Hash::make($new_password);
        $user->update();
        return [
            'status'=>'success',
            'message'=>'password updated successfully'
        ];
    }
    public function get_client_ip_env() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return explode(',',$ipaddress)[0];
    }
    public function requestOtp(){
        $user = \request()->user();
        $otp = random_int(1005,9967);
        $user->otp = Hash::make($otp);
        $user->update();
        $message = "Use OTP $otp to verify your phone in hauzisha";
        $user->notify(new verifyPhone($message));
        return [
            'status'=>'success',
            'message'=>'OTP send via sms'
        ];
    }
    public function verifyOtp(){
        $user = \request()->user();
        $data = \request()->all();
        $valid = Validator::make($data,[
            'otp'=>'required',
        ]);
        if (count($valid->errors())) {
            return response([
                'status' => 'failed',
                'errors' => $valid->errors()
            ], 422);
        }
        if(!Hash::check(\request('otp'),$user->otp)){
            return response([
                'status' => 'failed',
                'errors' => ['otp'=>['Invalid otp']]
            ], 422);
        }
        $user->phone_verified_at = now();
        $user->profile_step = 3;
        $user->update();
        return [
            'status'=>'success',
            'message'=>'Otp verified successfully'
        ];
    }

    public function listNotifications($status=null){
        $user = request()->user();
        if($status == 'count'){
            return [
                'unread'=>$user->unreadNotifications()->count(),
                'read'=>$user->readNotifications()->count()
            ];
        }
        if($status == 'unread') {
            $notifications = $user->unreadNotifications();
        } else {
            $notifications = $user->readNotifications();
        }
        $results = SearchRepo::of($notifications,'notifications', ['data'])
            ->make();
        return [
            'status'=>'success',
            'data'=>$results
        ];
    }
    public function readAllNotifications() {
        $user = request()->user();
        $user->unreadNotifications()->update([
            'read_at'=>now()
        ]);
        ShRepository::storeLog('read_notifications', 'Marked all notifications read');
        return [
            'status'=>'success',
            'message'=>'Notifications marked read'
        ];
    }
    public function markNotificationRead($id){
        $user = request()->user();
        $notification = $user->notifications()->find($id);
        $notification->read_at = now();
        $notification->update();
        return [
            'status'=>'success',
            'data'=>$notification
        ];
    }
}
