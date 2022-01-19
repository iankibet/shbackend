<?php
$controller = \App\Http\Controllers\Api\Admin\Settings\LogTypesController::class;
Route::post('/{id?}',[$controller,'storeLogType']);
Route::get('/list',[$controller,'listLogTypes']);
Route::get('/logtype/{id}',[$controller,'getLogType']);
Route::post('/logtype/delete/{id}',[$controller,'deleteLogType']);
