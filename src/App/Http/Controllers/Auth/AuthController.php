<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
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
    public function updateProfile(){
        $user = \request()->user();
        $data = \request()->all();
        $rules = [
            'phone'=>'required',
            'name'=>'required'
        ];
        $phone = \request('phone');
        $previous_phone = $user->phone;
        if($previous_phone != $phone){
            $rules['phone'] = 'required|unique:users';
        }
        $valid = Validator::make($data,$rules);
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
        if(strlen($user->phone)<10){
            return response([
                'status' => 'failed',
                'errors' => ['phone'=>['Invalid phone number provided']]
            ], 422);
        }
        $user->name = \request('name');
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
    public function logoutUser(){
        $user = request()->user();
        $user->currentAccessToken()->delete();
        return [
            'status'=>'success'
        ];
    }
}
