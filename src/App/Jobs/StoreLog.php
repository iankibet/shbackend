<?php

namespace App\Jobs;

use App\Events\NewLog;
use App\Models\Core\Log;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $id, $model_id,$model_class,$slug,$log,$device,$model;
    public function __construct($id, $model_id,$model_class,$slug,$log,$device,$model)
    {
        //
        $this->id = $id;
        $this->model_id = $model_id;
        $this->model_class = $model_class;
        $this->slug = $slug;
        $this->log = $log;
        $this->device = $device;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       $newLog = Log::create([
        'user_id'=>$this->id,
        'model_id'=>$this->model_id,
        'model'=>$this->model_class,
        'slug'=>$this->slug,
        'log'=>$this->log,
        'device'=>$this->device
    ]);
       event(new NewLog($newLog));
        $user = null;
        if($this->id){
            $user = User::find($this->id);
        }
        $logType = \App\Models\Core\LogType::where('slug','like',$this->slug)->first();
        if($logType && $logType->facebook_event_type && $user){
            \App\Jobs\PublishFacebookEvent::dispatch($user,$logType->facebook_event_type,$this->model);
        } else
            if(!$logType){
                $name = ucwords(str_replace('_',' ',$this->slug));
                \App\Models\Core\LogType::create([
                    'slug'=>$this->slug,
                    'name'=>$name,
                    'description'=>$name,
                    'user_id'=>0
                ]);
            }
        $strings = [
            'admin/view-request/'=>'house-requests/view/',
            '/tab/details'=>'',
            'admin/properties/property/'=>'properties/property/view/'
        ];
        $logs = Log::where('id','>',0);
        $logs = $logs->where(function($query) use($strings){
            foreach ($strings as $key=>$value){
                $query = $query->orWhere('log','like',"%$key%");
            }
            return $query;
        });
        $logs = $logs->get();
        foreach ($logs as $log){
            $name = $log->log;
            foreach ($strings as $key=>$value){
                $name = str_replace($key,$value,$name);
            }
            $log->log = $name;
            $log->update();
        }
    }
}
