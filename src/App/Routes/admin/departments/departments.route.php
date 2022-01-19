<?php
$controller = \App\Http\Controllers\Api\Admin\Departments\DepartmentsController::class;
Route::post('/{id?}',[$controller,'storeDepartment']);
Route::get('/list',[$controller,'listDepartments']);
Route::get('/department/{id}',[$controller,'getDepartment']);
Route::post('/permissions/{id}',[$controller,'updatePermissions']);
Route::post('/department/delete/{id}',[$controller,'deleteDepartment']);
Route::get('/all-permissions',[$controller,'allPermissions']);
