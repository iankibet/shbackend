<?php
$controller = \App\Http\Controllers\Api\Admin\Departments\Department\DepartmentController::class;
Route::get('/list-modules/{id}',[$controller,'listModules']);
Route::post('/add-module/{id}',[$controller,'addModule']);
Route::post('/permissions/{id}',[$controller,'setModulePermissions']);
Route::post('/permissions/{id}/{module}',[$controller,'updateModulePermissionsWithSlug']);
Route::get('/list-pending-modules/{id}',[$controller,'listPendingModules']);
Route::get('/list-all-modules/{role}/{id}',[$controller,'listAllModules']);
Route::get('/get-module-permissions/{module}',[$controller,'getModulePermissions']);
Route::post('/delete-department/{module}',[$controller,'removeModulePermissions']);
