<?php

use App\Models\Core\LogType;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

if (!function_exists('storeLog')) {
    function storeLog($slug,$log,$model=null)
    {
    $agent = new Jenssegers\Agent\Agent();
        $agent = new Agent();
        $os = $agent->platform();
        $browser = $agent->browser();
        $device = $os.' - '.$browser;
        $model_id = null;
        $model_class = null;
        if($model){
            $model_class = get_class($model);
            $model_id = $model->id;
        }
        $user = getUser();
        $id = 0;
        if(!$user && $model_class == \App\Models\User::class) {
            $user = $model;
        }
//        if(!$user && $model){
//            $user = \App\Models\User::find($model->user_id);
//        }
        if($user){
            $id = $user->id;
        }
        \App\Jobs\StoreLog::dispatch($id,$model_id,$model_class,$slug,$log,$device,$model);
    }
}

/**
 * Get current logged in user
 */
if (!function_exists('getUser')) {
    function getUser()
    {
        return request()->user();
    }
}
if(!function_exists('getCountry')){
    function getCountry(){
        $ip = request()->ip();
        $country='';
        $url = "http://www.geoplugin.net/json.gp?ip=$ip";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl,CURLOPT_HTTPHEADER, array(
            'Accept: application/json'
        ));
        $content = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $json_response = null;
        if($status==200 || $status==201) {
            $json_response = json_decode($content);
            $country=$json_response->geoplugin_countryName;
            $country=$json_response->geoplugin_countryCode;
        }
        return $country;
    }

}
if (!function_exists('getQuestionStatus')) {
    function getQuestionStatus($state)
    {
        return \App\Repositories\StatusRepository::getQuestionStatus($state);
    }
}
if (!function_exists('getQuestionBidStatus')) {
    function getQuestionBidStatus($state)
    {
        return \App\Repositories\StatusRepository::getQuestionBidStatus($state);
    }
}
if (!function_exists('getQuestionAssignStatus')) {
    function getQuestionAssignStatus($state)
    {
        return \App\Repositories\StatusRepository::getQuestionAssignStatus($state);
    }
}
if (!function_exists('getProjectStatus')) {
    function getProjectStatus($state)
    {
        return \App\Repositories\StatusRepository::getProjectStatus($state);
    }
}
if (!function_exists('getTutorName')) {
    function getTutorName($name)
    {
        $name=ucfirst($name);
       $name=explode(" ",$name);
       $first_name=$name[0];
        $initial='';
       if(count($name)>1){
           $initial = $name[1][0];
           $full_name=$first_name.' '.$initial;
           return $full_name;
       }
       return $first_name;
    }
}
if (!function_exists('getBidStatus')) {
    function getBidStatus($state)
    {
        return \App\Repositories\StatusRepository::getBidStatus($state);
    }
}
if (!function_exists('getTestQuestionStatus')) {

    function getTestQuestionStatus($state)
    {
        return \App\Repositories\StatusRepository::getTestQuestionStatus($state);
    }
}
if (!function_exists('getTaskStatus')) {
    function getTaskStatus($state)
    {
        return \App\Repositories\StatusRepository::getTaskStatus($state);
    }
}
if (!function_exists('getAssignStatus')) {
    function getAssignStatus($state)
    {
        return \App\Repositories\StatusRepository::getAssignStatus($state);
    }
}
if (!function_exists('countUserAssigns')) {
    function countUserAssigns($project_id)
    {
        $assigns=\App\Models\Core\Assign::join('tasks','tasks.id','=','assigns.task_id')
            ->where([
                'assigns.user_id'=>getUser()->id,
                'assigns.status'=>getAssignStatus('active'),
                'tasks.project_id'=>$project_id
            ])->count();
        return $assigns;
    }
}
if (!function_exists('getTopicStatus')) {
    function getTopicStatus($state)
    {
        return \App\Repositories\StatusRepository::getTopicStatus($state);
    }
}


if (!function_exists('getRevisionStatus')) {
    function getRevisionStatus($state)
    {
        return \App\Repositories\StatusRepository::getRevisionStatus($state);
    }
}
if (!function_exists('getTestStatus')) {
    function getTestStatus($state)
    {
        return \App\Repositories\StatusRepository::getTestStatus($state);
    }
}
if (!function_exists('getExamStatus')) {
    function getExamStatus($state)
    {
        return \App\Repositories\StatusRepository::getExamStatus($state);
    }
}
if (!function_exists('getFineStatus')) {
    function getFineStatus($state)
    {
        return \App\Repositories\StatusRepository::getFineStatus($state);
    }
}
if (!function_exists('getUserStatus')) {
    function getUserStatus($state)
    {
        return \App\Repositories\StatusRepository::getUserStatus($state);
    }
}
if (!function_exists('userCan')) {
    function userCan($slug)
    {
        return request()->user()->isAllowedTo($slug);
    }
}

if (!function_exists('userHas')) {
    function userHas($user_id,$slug)
    {
        return \App\Models\User::where('id','=',$user_id)->first()->isAllowedTo($slug);
    }
}
if (!function_exists('formatDeadline')) {
    function formatDeadline($date)
    {

        $date = \Carbon\Carbon::createFromTimestamp(strtotime($date));
        if ($date->isPast()) {
            $div_pre = '<strong class="text-danger">';
            $pre = "(late)";
            $days = $date->diffInDays();
            $hours = $date->copy()->addDays($days)->diffInHours();
            $minutes = $date->copy()->addDays($days)->addHours($hours)->diffInMinutes();
            $days_string = $days . 'D ';
            $hours_string = $hours . "H ";
        } else {
            $pre = '';
            $days = $date->diffInDays();
            $hours = $date->copy()->subDays($days)->diffInHours();
            if ($days > 0 || $hours > 5) {
                $div_pre = '<strong class="text-success">';
            } else {
                $div_pre = '<strong class="text-warning">';
            }
            $minutes = $date->copy()->subDays($days)->subHour($hours)->diffInMinutes();
            $days_string = $days . 'D ';
            $hours_string = $hours . "H ";
        }
        if ($days == 0)
            $days_string = "";
        if ($hours == 0)
            $hours_string = "";
        return $div_pre . $days_string . $hours_string . $minutes . "Mins " . $pre . '</strong>';
    }
}

if (!function_exists('sendGeneralNotification')) {
    if (!function_exists('sendGeneralNotification')) {
        function sendGeneralNotification($model,$roles_or_ids,$slug,$custom_message=null)
        {
            \App\Jobs\SendNotifications::dispatch($model,$roles_or_ids,$slug,$custom_message);
        }
    }
}


