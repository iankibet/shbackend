<?php
$middleWares = env('SH_API_MIDDLEWARE','auth:sanctum');
$middleWares = explode(',',$middleWares);
$middleWares[] = 'sh_auth';
Route::group(['middleware' => $middleWares, 'prefix'=>'api/sh-ql'],function(){
    $controller = \Iankibet\Shbackend\App\Http\FrameworkControllers\Ql\QlController::class;
    Route::controller($controller)->group(function(){
       Route::get('/','handleQuery');
       Route::post('/','handleMutation');
       Route::post('/store/{model}','createModel');
       Route::post('/add/{model}','createModel');
       Route::post('/create/{model}','createModel');
       Route::post('/update/{model}/{id}','updateModel');
       Route::post('/edit/{model}/{id}','updateModel');
   });
});
