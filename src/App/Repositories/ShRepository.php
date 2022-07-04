<?php

namespace Iankibet\Shbackend\App\Repositories;

use Monolog\LogRecord;

class ShRepository
{
 public static function storeLog($slug,$description){
     LogsRepository::storeLog($slug,$description);
 }
    public static function saveModel($data){
        $model_saver = New ModelSaverRepository();
        $model = $model_saver->saveModel($data);
        return $model;
    }
    public static function autoSaveModel($data){
        $model_saver = New ModelSaverRepository();
        $model = $model_saver->saveModel($data);
        return $model;
    }
    public static function getValidationFields($model_class, $fillables = null){
        $data = request()->all();
        if(!$fillables && $model_class){
            $model = new $model_class;
            $fillables = $model->getFillable();
        }else
        {
            $model_string = decrypt($data['form_model']);
            $model = new $model_string();
            $fillables = $model->getFillable();
        }
        $validation_array =  [];
        foreach($fillables as $field){
            if($field == 'name'){
                $validation_array[$field] = 'required|max:255';
            } else {
                $validation_array[$field] = 'required';
            }
        }
        if (in_array("file",$fillables)){
            $validation_array['file'] = 'required|max:50000';
        }
        $validation_array['id']='';
        $validation_array['form_model']='';
        unset($validation_array['form_model']);
        return $validation_array;
    }
}
