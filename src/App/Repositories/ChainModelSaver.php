<?php
namespace Iankibet\Shbackend\App\Repositories;
use Illuminate\Support\Facades\Validator;

class ChainModelSaver
{
    protected static $data;
    protected static $validationRules;
    protected static $model;
    protected static $forceFill;
    protected static $instance;
    public static function beginAutoSaveModel($model = null,array $data=[],array $forceFill=[]){
        self::$instance = new self();
        if($model ){
            if(is_string($model)){
                $model = new $model();
            }
            self::$model = $model;
        }
        self::$data = $data;
        self::$forceFill = $forceFill;
        return self::$instance;
    }
    public static function setModel($model = null){
        if($model ){
            if(is_string($model)){
                $model = new $model();
            }
            self::$model = $model;
        }
        return self::$instance;
    }
    public static function setDataFromRequest(){
        $data = request()->all();
        if(self::$data){
            $data = array_merge(self::$data,$data);
        }
        self::$data = $data;
        return self::$instance;
    }
    public static function getDataFromRequest(){
        return self::setDataFromRequest();
    }
    public static function setData(array $data){
        if(self::$data){
            $data = array_merge(self::$data,$data);
        }
        self::$data = $data;
        return self::$instance;
    }
    public static function setForcefillData(array $forceFill){
        if(self::$forceFill){
            $forceFill = array_merge(self::$forceFill,$forceFill);
        }
        self::$forceFill = $forceFill;
        return self::$instance;
    }

    public static function forceFillData(array $forceFill){
        return self::setForcefillData($forceFill);
    }
    public static function forceFill(array $forceFill){
        return self::setForcefillData($forceFill);
    }

    public static function setValidationRules(array $rules){
        $realRules = [];
        foreach ($rules as $key=>$value){
            if(is_int($key)){
                $realRules[$value] = 'required';
            } else {
                $realRules[$key] = $value;
            }
        }
        self::$validationRules = $realRules;
        return self::$instance;
    }
    public static function setValidationRulesFromFillable(array $moreRules=[]){
        $model = self::$model;
        $fillables = $model->getFillable();
        $allRules = array_merge($fillables,$moreRules);
        $realRules = [];
        foreach ($allRules as $key=>$value){
            if(is_int($key)){
                $realRules[$value] = 'required';
            } else {
                $realRules[$key] = $value;
            }
        }
        self::$validationRules = $realRules;
        return self::$instance;
    }
    public static function validateFillable(array $moreRules=[]){
        $model = self::$model;
        $fillables = $model->getFillable();
        $allRules = array_merge($fillables,$moreRules);
        $realRules = [];
        foreach ($allRules as $key=>$value){
            if(is_int($key)){
                $realRules[$value] = 'required';
            } else {
                $realRules[$key] = $value;
            }
        }
        self::$validationRules = $realRules;
        return self::$instance;
    }

    public static function save(){
        if(!self::$model){
            throw new \Exception("Model not set, did you call setModel?");
        }
        if(self::$validationRules){
            //validation rules set, validate data
            $valid = Validator::make(self::$data,self::$validationRules);
            if($valid->errors()->count()){
                $response = [
                    'errors'=>$valid->errors()
                ];
                abort(response($response,422));
            }
        }

        //all checks passed, save model
        $modelSaver = new ModelSaverRepository();
        return $modelSaver->saveModel(self::$model,self::$data,self::$forceFill);
    }

    public static function commit(){
        return self::save();
    }
    public static function complete(){
        return self::save();
    }
    public static function saveModel(){
        return self::save();
    }
}
