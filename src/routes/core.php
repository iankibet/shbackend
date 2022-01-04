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
$apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;
Route::group(['middleware' => ['auth:api','sh_auth'], 'prefix'=>'api'], function () {
    $routes_path = base_path('routes/api');
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
        $ext_route = str_replace('web', '', $ext_route);
        if ($ext_route)
            $ext_route = '/' . $ext_route;
        $prefix = strtolower($prefix . $ext_route);
        $namespace = implode('\\', $arr);
        $namespace = str_replace('\\\\','\\',$namespace);
        Route::group(['namespace' => $namespace, 'prefix' => $prefix], function () {
            require $this->route_path;
        });
    }
});
