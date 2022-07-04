<?php
/**
 * Created by PhpStorm.
 * User: iankibet
 * Date: 5/12/18
 * Time: 10:16 AM
 */

namespace Shara\Framework\Iankibet\Shbackend\App\Repositories;


class StatusRepository
{
    public function getTaskStatus($state)
    {
        $statuses = [
            'pending' => 0,
            'in_progress' => 1,
            'completed' =>2,
            'canceled' => 3
        ];
        if (is_numeric($state))
            $statuses = array_flip($statuses);

        return self::checkState($state,$statuses);
    }
    protected function checkState($state,$statuses){
        if(is_array($state)){
            $states  = [];
            foreach($state as $st){
                $states[] = $statuses[$st];
            }

            return $states;
        }
        return $statuses[$state];
    }

}
