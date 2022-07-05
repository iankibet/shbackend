<?php
/**
 * Created by PhpStorm.
 * User: kemboi
 * Date: 9/13/18
 * Time: 6:13 PM
 */

namespace Iankibet\Shbackend\App\Repositories;


use App\Models\Core\Log;
use App\Models\Core\LogType;
class LogsRepository
{

    public static function storeLog($slug,$description,$model=null)
    {
        ShRepository::storeLog($slug,$description,$model);
    }
}
