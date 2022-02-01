<?php
$controller = \App\Http\Controllers\Api\Errors\ErrorExceptionsController::class;
Route::post('/{id?}',[$controller,'storeErrorException']);
Route::post('/resolve/{id}',[$controller,'resolveError']);
Route::get('/list/{status}',[$controller,'listErrorExceptions']);
Route::get('/errorexception/{id}',[$controller,'getErrorException']);
Route::post('/errorexception/delete/{id}',[$controller,'deleteErrorException']);
