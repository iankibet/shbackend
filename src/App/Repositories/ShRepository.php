<?php

namespace Iankibet\Shbackend\App\Repositories;

use App\Models\Core\Log;
use App\Models\User;
use Iankibet\Shbackend\App\Events\ShNewLog;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Monolog\LogRecord;

class ShRepository
{
    public static function beginAutoSaveModel($model,array $data=[],array $forceFill = []){
        return ChainModelSaver::beginAutoSaveModel($model,$data,$forceFill);
    }
    public static function getCachedQueryResults($query,$period=null){
        $repo = new CachingRepository();
        return $repo->getCachedQueryResults($query,$period);
    }
    public static function getChartData($query,$type='stock',$fields=[],$date_field='created_at'){
        return GraphStatsRepository::getDrawables($query,$type,$fields,$date_field);
    }

    public static function autoSaveModelFromRequest($model,$forceFill = null){
        $data = request()->all();
        return self::autoSaveModel($model,$data,$forceFill ?? []);
    }
    public static function validateRequest($rules,$data = null){
        $realRules = [];
        foreach ($rules as $key=>$value){
            if(is_int($key)){
                $realRules[$value] = 'required';
            } else {
                $realRules[$key] = $value;
            }
        }
        if(!$data){
            $data = request()->all();
        }
        $valid = Validator::make($data,$realRules);
        if($valid->errors()->count()){
            $response = [
                'errors'=>$valid->errors()
            ];
            abort(response($response,422));
        }
        return true;
    }
    public static function storeLog($slug, $log, $model = null)
    {
        $agent = new Agent();
        $os = $agent->platform();
        $browser = $agent->browser();
        $ip = self::get_client_ip_env();
        $device = $os . ' - ' . $browser;
        $model_id = null;
        $model_class = null;
        if ($model) {
            $model_class = get_class($model);
            $model_id = $model->id;
        }
        $user = request()->user();
        $id = 0;
        if (!$user && $model_class == \App\Models\User::class) {
            $user = $model;
        }
        if ($user) {
            $id = $user->id;
        }
        $data = [
            'user_id' => $id,
            'model_id' => $model_id,
            'model' => $model_class,
            'slug' => $slug,
            'log' => $log,
            'device' => $device,
            'ip_address' => $ip
        ];
        ShNewLog::dispatch($data);
        /**
         * TODO Document how new logs are to be dispatched. It's as simple as storeLog methods dispatches a
         * ShNewLog event with the data array as the first parameter.
         */
    }

    public static function autoSaveModel($model, array $data, ?array $forceFill = [])
    {
        $model_saver = new ModelSaverRepository();
        $model = $model_saver->saveModel($model, $data, $forceFill);
        return $model;
    }

    public static function getFillables($model_class)
    {
        if (is_string($model_class)) {
            $model = new $model_class;
        } else {
            $model = $model_class;
        }

        $fillables = $model->getFillable();
        return $fillables;
    }

    /**
     * @param $model_class
     * @param array $validationFields
     * @return mixed
     * @deprecated use getValidationRules instead
     */
    public static function getValidationFields($model_class, array $validationFields = [])
    {
        return self::getValidationRules($model_class, $validationFields);
    }

    public static function getValidationRules($model_class, array $additionalFields = [])
    {
        $fillables = self::getFillables($model_class);
        if (count($additionalFields)) {
            $fillables = array_merge($fillables, $additionalFields);
        }
        $validation_array = [];
        $names = ['name'];
        $descriptions = ['description'];
        $emails = ['email'];
        $numbers = ['age', 'year', 'height'];
        foreach ($fillables as $field) {
            if (in_array($field, $names)) {
                $validation_array[$field] = 'required|max:255';
            } else if (in_array($field, $emails)) {
                $validation_array[$field] = 'required|email|max:255';
            } else if (in_array($field, $descriptions)) {
                $validation_array[$field] = '';
            } else {
                $validation_array[$field] = 'required';
            }
        }
        if (in_array("file", $fillables)) {
            $validation_array['file'] = 'required|max:50000';
        }
        $validation_array['id'] = '';
        return $validation_array;
    }

    public static function translateStatus($state, $statuses)
    {
        if (is_numeric($state) && !is_numeric(array_keys($statuses)[0]))
            $statuses = array_flip($statuses);

        if (!is_numeric($state) && is_numeric(array_keys($statuses)[0]))
            $statuses = array_flip($statuses);

        if (is_array($state)) {
            $states = [];
            foreach ($state as $st) {
                $states[] = $statuses[$st];
            }
            return $states;
        }
        if (isset($statuses[$state])) {
            return $statuses[$state];
        }
        throw new \Exception("$state state not found in " . implode(',', array_keys($statuses)));
    }

    protected static function get_client_ip_env()
    {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return explode(',', $ipaddress)[0];
    }
}
