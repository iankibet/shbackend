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

    public function saveModel(string $model, $request_data,  ? array $forceFill = []){
        if(isset($request_data['ypos'])){
            session()->flash('ypos',$request_data['ypos']);
        }
        $request_data = (object)$request_data;
        $fillables = ShRepository::getFillables($model);
        if(isset($forceFill['id'])){
            $model = $model::findOrFail($forceFill['id']);
            unset($forceFill['id']);
        } else {
            $model = new $model;
        }
        //set fillable values
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
