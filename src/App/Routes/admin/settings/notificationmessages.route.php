<?php
$controller = \App\Http\Controllers\Api\Admin\Settings\NotificationMessagesController::class;
Route::post('/',[$controller,'storeNotificationMessage']);
Route::get('/list',[$controller,'listNotificationMessages']);
Route::get('/notificationmessage/{id}',[$controller,'getNotificationMessage']);
Route::post('/notificationmessage/delete/{id}',[$controller,'deleteNotificationMessage']);
