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

    public function saveModel($request_data){
        if(isset($request_data['ypos'])){
//            dd($request_data);
            session()->flash('ypos',$request_data['ypos']);
        }
        $request_data = (object)$request_data;
        $class = decrypt($request_data->form_model);
        $model = $class::findOrNew(@$request_data->id);
        foreach($request_data as $key=>$value){
            if(!in_array($key,['id','ypos','xpos','fid','_token','entity_name','form_model','password_confirmation','tab'])){
                if($key == 'password'){
                    $model->$key = bcrypt($value);
                }else{
                    $model->$key = $value;
                }
            }
        }
        $model->save();
        return $model;
    }
}
