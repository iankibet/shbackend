<?php

namespace App\Jobs;

use App\Models\Core\HouseRequest;
use App\Models\Core\Suggestion;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $model,$roles_or_ids,$slug,$custom_message;
    public function __construct($model,$roles_or_ids,$slug,$custom_message=null)
    {
        //
        $this->model = $model;
        $this->roles_or_ids = $roles_or_ids;
        $this->slug = $slug;
        $this->custom_message = $custom_message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notificationMessage = \App\Models\Core\NotificationMessage::where('slug',$this->slug)->first();
        if(!$notificationMessage){
            throw new \Exception("Notification message with slug $this->slug not found");
        }
        $roles_or_ids = $this->roles_or_ids;
        if(!is_array($roles_or_ids))
            $roles_or_ids = [$roles_or_ids];
        $notifiable = null;
        if(count($roles_or_ids) == 0)
            return;
        if (is_numeric($roles_or_ids[0])){
            $notifiable = \App\Models\User::whereIn('id',$roles_or_ids)->where('notification_enabled',1)->get();
        }else{
            $notifiable = \App\Models\User::whereIn('role',$roles_or_ids)->where('notification_enabled',1)->get();
        }
        $model_arr = $this->model->toArray();
        if(get_class($this->model) == User::class) {
            $referrer = User::find($this->model->referrer_id);
            if($referrer){
                $model_arr['referrer'] = $referrer->name;
            }
        }
        if(get_class($this->model) == HouseRequest::class){
            $movein = $model_arr['move_in_date'];
            $movein = Carbon::createFromTimestamp(strtotime($movein))->format('d/m/Y');
            $model_arr['client'] = @$this->model->user->name;
            $model_arr['budget'] = 'From '.$model_arr['currency'].' '.number_format($model_arr['min_budget']).' to '.$model_arr['currency'].' '.number_format($model_arr['max_budget']);
            $model_arr['request_type'] = str_replace('Buy','Sale',$model_arr['request_type']);
            $model_arr['movein'] = $movein;
            $model_arr['move_in'] = $movein;
            $location_arr = json_decode($model_arr['locations']);
            if($location_arr){
                $locations = [];
                foreach ($location_arr as $loc){
                    $locations[] = $loc->name;
                }
                $model_arr['location'] = implode(',',$locations);
                $model_arr['locations'] = implode(',',$locations);
            }else{
                $model_arr['location'] = '';
                $model_arr['locations'] = '';
            }
        }
        if(get_class($this->model) == Suggestion::class){
            $model_arr['like_type'] = $model_arr['like'] == 1 ? 'Liked':'Disliked';
        }
        foreach (array_keys($model_arr) as $key){
            if(!is_object($model_arr[$key]) && !is_array($model_arr[$key])) {
                $notificationMessage->sms = str_replace('{' . $key . '}', $model_arr[$key], $notificationMessage->sms);
                $notificationMessage->mail = str_replace('{' . $key . '}', $model_arr[$key], $notificationMessage->mail);
                $notificationMessage->subject = str_replace('{' . $key . '}', $model_arr[$key], $notificationMessage->subject);
                $notificationMessage->action_label = str_replace('{' . $key . '}', $model_arr[$key], $notificationMessage->action_label);
                $notificationMessage->action_url = str_replace('{' . $key . '}', $model_arr[$key], $notificationMessage->action_url);
            }
        }
        $notificationMessage->subject = str_replace('{custom_message}',$this->custom_message,$notificationMessage->subject);
        $notificationMessage->mail = str_replace('{custom_message}',$this->custom_message,$notificationMessage->mail);
        $notificationMessage->sms = str_replace('{custom_message}',$this->custom_message,$notificationMessage->sms);

        if(count($notifiable)){
            \Illuminate\Support\Facades\Notification::send($notifiable,new GeneralNotification($notificationMessage));
        }
    }
}
