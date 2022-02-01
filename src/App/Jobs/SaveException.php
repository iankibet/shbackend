<?php

namespace App\Jobs;

use App\Models\Core\ErrorException;
use App\Models\User;
use App\Notifications\ErrorOccured;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SaveException implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user_id,$message,$file,$line,$url;
    public function __construct($user,$message,$file=null,$line=null,$url=null)
    {
        //
        $this->user_id = $user ? $user->id:0;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
//        dd($this->user_id);
        try {
            $exception = ErrorException::create([
                'user_id'=>$this->user_id,
                'error'=>$this->message,
                'file'=>$this->file,
                'line'=>$this->line,
                'url'=>$this->url
            ]);
            if(env('ERROR_HANDLERS')) {
                $users = User::whereIn('id',explode(',',env('ERROR_HANDLERS')))->get();
                Notification::send($users, new ErrorOccured($exception));
            }
        }catch (\Exception $exception) {

        }

    }
}
