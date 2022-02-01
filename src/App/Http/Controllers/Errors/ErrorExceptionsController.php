<?php

namespace App\Http\Controllers\Api\Errors;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Core\ErrorException;
use App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class ErrorExceptionsController extends Controller
{

     public function __construct()
        {
            $this->api_model = ErrorException::class;
        }
        public function storeErrorException(){
            $data = \request()->all();
            $valid = Validator::make($data,$this->getValidationFields());
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $data['form_model'] = encrypt($this->api_model);
            // $data['user_id'] = \request()->user()->id;
            $errorexception = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'errorexception'=>$errorexception
            ];
        }

        public function listErrorExceptions($status){
            $user = \request()->user();
            if($status == 'pending') {
                $status = 0;
            } else {
                $status = 1;
            }
            $errorexceptions = ErrorException::leftJoin('users','users.id','=','error_exceptions.user_id')
            ->where('error_exceptions.status',$status)
                ->select('error_exceptions.*', 'users.name as user');
            $table = 'error_exceptions';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($errorexceptions,$table,$search_keys)
                    ->make(true)
            ];
        }

        public function resolveError($id){
            $data = \request()->all();
            $valid = Validator::make($data,[
                'resolve_message'=>'required'
            ]);
            if (count($valid->errors())) {
                return response([
                    'status' => 'failed',
                    'errors' => $valid->errors()
                ], 422);
            }
            $error = ErrorException::find($id);
            $error->status = 1;
            $error->resolve_message = \request('resolve_message');
            $error->update();
            storeLog('resolved_error',"Resolved error $error->id with message <strong>$error->resolve_message</strong>");
            return [
                'status'=>'success',
                'message'=>'Resolved successfully',
                'error'=>$error
            ];
        }

}
