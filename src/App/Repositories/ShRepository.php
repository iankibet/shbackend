<?php

namespace Iankibet\Shbackend\App\Repositories;

use App\Models\Core\Log;
use App\Models\User;
use Jenssegers\Agent\Agent;
use Monolog\LogRecord;

class ShRepository
{
 public static function storeLog($slug,$log, $model=null){
     $agent = new Agent();
     $os = $agent->platform();
     $browser = $agent->browser();
     $ip = self::get_client_ip_env();
     $device = $os.' - '.$browser;
     $model_id = null;
     $model_class = null;
     if($model){
         $model_class = get_class($model);
         $model_id = $model->id;
     }
     $user = request()->user();
     $id = 0;
     if(!$user && $model_class == \App\Models\User::class) {
         $user = $model;
     }
     if($user){
         $id = $user->id;
     }
     $newLog = Log::create([
         'user_id'=>$id,
         'model_id'=>$model_id,
         'model'=>$model_class,
         'slug'=>$slug,
         'log'=>$log,
         'device'=>$device,
         'ip_address'=>$ip
     ]);
//     event(new NewLog($newLog));
     $logType = \App\Models\Core\LogType::where('slug','like',$slug)->count();
     if(!$logType){
         $name = ucwords(str_replace('_',' ',$slug));
         \App\Models\Core\LogType::create([
             'slug'=>$slug,
             'name'=>$name,
             'description'=>$name,
             'user_id'=>0
         ]);
     }
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
        $names = ['name'];
        $descriptions = ['description'];
        $emails = ['email'];
        $numbers = ['age','year','height'];
        foreach($fillables as $field){
            if(in_array($field, $names)){
                $validation_array[$field] = 'required|max:255';
            }else if(in_array($field, $emails)){
                $validation_array[$field] = 'required|email|max:255';
            } else if(in_array($field, $descriptions)){
                $validation_array[$field] = '';
            }else {
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
    public static function translateStatus($state, $statuses){
        if (is_numeric($state))
            $statuses = array_flip($statuses);
        if(is_array($state)){
            $states  = [];
            foreach($state as $st){
                $states[] = $statuses[$st];
            }

            return $states;
        }
//        dd($statuses);
        if(isset($statuses[$state])){
            return $statuses[$state];
        }
        throw new \Exception("$state state not found in ".implode(',',array_keys($statuses)));
    }
    protected static function get_client_ip_env() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return explode(',',$ipaddress)[0];
    }
}
