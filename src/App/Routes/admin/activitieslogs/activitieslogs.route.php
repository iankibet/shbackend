<?php
$controller = \App\Http\Controllers\Api\Admin\Activitieslogs\ActivitieslogsController::class;
Route::post('/{id?}',[$controller,'storeLog']);
Route::get('/list',[$controller,'listLogs']);
Route::get('/log/{id}',[$controller,'getLog']);
Route::post('/log/delete/{id}',[$controller,'deleteLog']);
