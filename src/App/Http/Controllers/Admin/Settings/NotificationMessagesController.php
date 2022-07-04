<?php

namespace App\Http\Controllers\Api\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Core\NotificationMessage;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class NotificationMessagesController extends Controller
{
    
     public function __construct()
        {
            $this->api_model = NotificationMessage::class;
        }
        public function storeNotificationMessage(){
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
            $notificationmessage = $this->autoSaveModel($data);
            return [
              'status'=>'success',
              'notificationmessage'=>$notificationmessage
            ];
        }

        public function listNotificationMessages(){
            $user = \request()->user();
            $notificationmessages = new NotificationMessage();
            $table = 'notification_messages';
            $search_keys = array_keys($this->getValidationFields());
            return[
                'status'=>'success',
                'data'=>SearchRepo::of($notificationmessages,$table,$search_keys)
                    ->make(true)
            ];
        }

        public function getNotificationMessage($id){
            $user = \request()->user();
    //        $notificationmessage = NotificationMessage::find($id);
            $notificationmessage = NotificationMessage::where('user_id',$user->id)->find($id);
            return [
                'status'=>'success',
                'notificationmessage'=>$notificationmessage
            ];
        }
        public function deleteNotificationMessage($id){
            $user = \request()->user();
    //        $notificationmessage = NotificationMessage::find($id);
            $notificationmessage = NotificationMessage::where('user_id',$user->id)->find($id);
            $notificationmessage->delete();
            return [
                'status'=>'success',
            ];
        }

}
