<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::group(['middleware' => ['api'], 'prefix'=>'api'], function () {
    $apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;
    Route::post('auth/login',[$apiAuthController,'login']);
//    Route::post('auth/register',[$apiAuthController,'registerAgent']);
    Route::post('auth/reset',[$apiAuthController,'resetPassword']);
    Route::post('auth/forgot',[$apiAuthController,'forgotPassword']);
    Route::post('auth/register',[$apiAuthController,'register']);
});

Route::group(['middleware' => [env('SH_API_MIDDLEWARE','auth:sanctum'),'sh_auth'], 'prefix'=>'api'], function () {
    $apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;
    Route::post('auth/notifications/read-all',[$apiAuthController,'readAllNotifications']);
    Route::get('auth/notifications/{status}',[$apiAuthController,'listNotifications']);
    Route::post('auth/notifications/mark-read/{id}',[$apiAuthController,'markNotificationRead']);
    Route::post('auth/user',[$apiAuthController,'updateProfile']);
    Route::post('auth/request-otp',[$apiAuthController,'requestOtp']);
    Route::post('auth/verify-otp',[$apiAuthController,'verifyOtp']);
    Route::get('auth/user',[$apiAuthController,'getUser']);
    Route::get('auth/logout',[$apiAuthController,'logoutUser']);
    $apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;
    $routes_path = base_path('routes/api');
    if(file_exists($routes_path)) {
        $route_files = File::allFiles(base_path('routes/api'));
        foreach ($route_files as $file) {
            $path = $file->getPath();
            $file_name = $file->getFileName();
            $prefix = str_replace($file_name, '', $path);
            $prefix = str_replace($routes_path, '', $prefix);
            $file_path = $file->getPathName();
            $this->route_path = $file_path;
            $arr = explode('/', $prefix);
            $len = count($arr);
            $main_file = $arr[$len - 1];
            $arr = array_map('ucwords', $arr);
            $arr = array_filter($arr);
            $ext_route = str_replace('user.route.php', '', $file_name);
            $ext_route = str_replace($main_file.'.', '.', $ext_route);
            $ext_route = str_replace('.route.php', '', $ext_route);
//            $ext_route = str_replace('web', '', $ext_route);
            if ($ext_route)
                $ext_route = '/' . $ext_route;
            $prefix = strtolower($prefix . $ext_route);
            $namespace = implode('\\', $arr);
            $namespace = str_replace('\\\\','\\',$namespace);
            Route::group(['namespace' => $namespace, 'prefix' => $prefix], function () {
                require $this->route_path;
            });
        }
    }
});
