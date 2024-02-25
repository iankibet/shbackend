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
    Route::post('auth/forgot',[$apiAuthController,'forgotPassword']);
    Route::post('auth/register',[$apiAuthController,'register']);
});
$middleWares = config('shconfig.api_middleware');
$middleWares = explode(',',$middleWares);
$middleWares[] = 'sh_auth';
Route::group(['middleware' => $middleWares, 'prefix'=>'api'], function () {
    $apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;
    Route::post('auth/user',[$apiAuthController,'updateProfile']);
    Route::get('auth/user',[$apiAuthController,'getUser']);
    Route::post('auth/reset',[$apiAuthController,'resetPassword']);
    Route::post('auth/profile-picture',[$apiAuthController,'uploadProfilePicture']);
    Route::post('auth/logout',[$apiAuthController,'logoutUser']);
    $apiAuthController = \App\Http\Controllers\Api\Auth\AuthController::class;

    //permission Routes
    $departmentsController = \Iankibet\Shbackend\App\Http\FrameworkControllers\Sh\ShDepartments::class;
    Route::post('/sh-departments/{id?}',[$departmentsController,'storeDepartment']);
    Route::get('sh-departments/list',[$departmentsController,'listDepartments']);
    Route::get('sh-departments/department/{id}',[$departmentsController,'getDepartment']);
    Route::post('sh-departments/permissions/{id}',[$departmentsController,'updatePermissions']);
    Route::post('sh-departments/department/delete/{id}',[$departmentsController,'deleteDepartment']);
    Route::get('sh-departments/all-permissions',[$departmentsController,'allPermissions']);

    $departmentController = Iankibet\Shbackend\App\Http\FrameworkControllers\Sh\Department\ShDepartmentController::class;
    Route::get('/sh-departments/department/list-modules/{id}',[$departmentController,'listModules']);
    Route::post('/sh-departments/department/add-module/{id}',[$departmentController,'addModule']);
    Route::post('/sh-departments/department/permissions/{id}',[$departmentController,'setModulePermissions']);
    Route::post('/sh-departments/department/permissions/{id}/{module}',[$departmentController,'updateModulePermissionsWithSlug']);
    Route::get('/sh-departments/department/list-pending-modules/{id}',[$departmentController,'listPendingModules']);
    Route::get('/sh-departments/department/list-all-modules/{role}/{id}',[$departmentController,'listAllModules']);
    Route::get('/sh-departments/department/get-module-permissions/{module}',[$departmentController,'getModulePermissions']);
    Route::post('/sh-departments/department/delete-department/{module}',[$departmentController,'removeModulePermissions']);

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
            if($main_file.'.route.php' === $ext_route)
                $ext_route = str_replace($main_file.'.', '.', $ext_route);
            $ext_route = str_replace('.route.php', '', $ext_route);
//            $ext_route = str_replace('web', '', $ext_route);
            if ($ext_route)
                $ext_route = '/' . $ext_route;
            $prefix = strtolower($prefix . $ext_route);
            $namespace = implode('\\', $arr);
            $namespace = str_replace('\\\\','\\',$namespace);
            Route::group(['prefix' => $prefix], function () {
                require $this->route_path;
            });
        }
    }
});
