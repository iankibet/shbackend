<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Core\LogType;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class LogTypesController extends Controller
{

     public function __construct()
        {
            $this->api_model = LogType::class;
        }
        public function ShRepository::storeLogType(){
            $data = \request()->all();
            $rules = $this->getValidationFields();
            unset($rules['user_id']);
            unset($rules['facebook_event_type']);
            $valid = Validator::make($data,$rules);
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $data['form_model'] = encrypt($this->api_model);
             $data['user_id'] = \request()->user()->id;
            $logtype = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'logtype'=>$logtype
            ];
        }

        public function listLogTypes(){
            $user = \request()->user();
            $logtypes = new LogType();
            $table = 'log_types';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($logtypes,$table,$search_keys)
                    ->make(true)
            ];
        }

        public function getLogType($id){
            $user = \request()->user();
    //        $logtype = LogType::find($id);
            $logtype = LogType::where('user_id',$user->id)->find($id);
            return [
                'status'=>'success',
                'logtype'=>$logtype
            ];
        }
        public function deleteLogType($id){
            $user = \request()->user();
    //        $logtype = LogType::find($id);
            $logtype = LogType::where('user_id',$user->id)->find($id);
            $logtype->delete();
            return [
                'status'=>'success',
            ];
        }

}
