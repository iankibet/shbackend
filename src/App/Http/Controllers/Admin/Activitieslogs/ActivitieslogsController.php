<?php

namespace App\Http\Controllers\Api\Admin\Activitieslogs;

use App\Http\Controllers\Controller;
use App\Models\Core\Log;
use App\Models\User;
use Iankibet\Shbackend\App\Repositories\SearchRepo;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\DocBlock\Tags\Property;

class ActivitieslogsController extends Controller
{
    public function __construct()
    {
        $this->api_model = Log::class;
    }

    public function listLogs()
    {
        $logs = Log::join('users', 'users.id', '=', 'logs.user_id')
            ->select('logs.*', 'users.name as user');
        $search_keys = ['slug', 'log'];
        $search_keys[] = 'users.name';
        return [
            'status' => 'success',
            'data' => SearchRepo::of($logs, 'logs', $search_keys)
                ->make(true)
        ];
    }
    public function getLog($id)
    {
        $user = \request()->user();
        //        $log = Log::find($id);
        $log = Log::where('user_id', $user->id)->find($id);
        return [
            'status' => 'success',
            'log' => $log
        ];
    }

    public function deleteLog($id)
    {
        $user = \request()->user();
        //        $log = Log::find($id);
        $log = Log::where('user_id', $user->id)->find($id);
        $log->delete();
        return [
            'status' => 'success',
        ];
    }
}
