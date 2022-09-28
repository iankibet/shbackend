<?php
/**
 * Created by PhpStorm.
 * User: iankibet
 * Date: 4/13/17
 * Time: 9:01 AM
 */

namespace Iankibet\Shbackend\App\Repositories;


use function bcrypt;
use function decrypt;
use function session;

class ModelSaverRepository
{

    public function saveModel($model, $request_data,  ? array $forceFill = []){
        if(is_string($model)){
            $model = new $model;
        }
        $fillables = ShRepository::getFillables($model);
        //set fillable values
        if($forceFill){
            unset($forceFill['id']);
        }
        foreach($request_data as $key=>$value){
            if(in_array($key,$fillables)){
                $model->$key = $value;
            }
        }
        foreach($forceFill as $key=>$value){
            $model->$key = $value;
        }
        $model->save();
        return $model;
    }
}
