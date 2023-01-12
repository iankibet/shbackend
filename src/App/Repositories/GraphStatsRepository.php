<?php
namespace Iankibet\Shbackend\App\Repositories;


use App\Repositories\Cache\CacheRepository;
use Carbon\Carbon;

class GraphStatsRepository
{
    public static function getDrawables($query,$type='stock',$fields=[],$date_field='created_at'){
        $from = Carbon::parse(request('from'));
        $to = Carbon::parse(request('to'));
        $table = $query->getModel()->getTable();
        $diff_days = $from->diffInDays($to);
        $cachingRepo = new CachingRepository();
        return $cachingRepo->getCachedQueryResults($query,null,$type, $from, $to, $date_field,$fields);
    }

    public static function getChartData($items,$from,$to, $date_field,$fields){
        $diff_days = $from->diffInDays($to);
        $precision = 2;
        $unformatted_orders = [];
        $labels = [];
        $values  = [];
        $amounts = [];
        if($diff_days == 0){
            //show hours
            $days_arr = [];
            for($i=0;$i<$d=24;$i++){
                $date = $from->addHours($i);
                $label = (string)$date->format('d - D h A');
                $days_arr[$date->hour]=[
                    'label'=>$label,
                    'count'=>0,
                ];
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $key = $field;
                    }
                    else{
                        $key = $field['label'];
                    }
                    $days_arr[$date->hour][$key] = 0;
                }

                $from->subHours($i);
            }

            foreach ($items as $item) {
                $created_date = Carbon::createFromTimestamp(strtotime($item->$date_field));
                $created = $created_date->hour;
                $days_arr[$created]['count']+=1;

                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $days_arr[$created][$field]+=round($item->$field,$precision);
                    }else{
                        $query = key($field);
                        $value = $field[$query];
                        $days_arr[$created][$field['label']]+=self::checkQueryValidity($item,$field,$key);
                    }
                }

            }
            $unformatted_orders = $days_arr;

        }elseif($diff_days < 8){
            //show days of the week
            $days_arr = [];
            for($i=0;$i<$diff_days+1;$i++){
                $date = $from->addDays($i);
                $label = (string)$date->format('D-d');
                $days_arr[$date->toDateString()]=[
                    'label'=>$label,
                    'count'=>0,
                ];
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $key = $field;
                    }
                    else{
                        $key = $field['label'];
                    }
                    $days_arr[$date->toDateString()][$key] = 0;
                }
                $from->subDays($i);
            }


            foreach ($items as $item) {
                $created_date = Carbon::createFromTimestamp(strtotime($item->$date_field));
                $created = $created_date->toDateString();
                $days_arr[$created]['count']+=1;

                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $days_arr[$created][$field]+=round($item->$field,$precision);
                    }else{
                        $days_arr[$created][$field['label']]+=self::checkQueryValidity($item,$field,$key);
                    }
                }
            }
            $unformatted_orders = $days_arr;

        }elseif($diff_days<32){
            //show days of the month
            $days_arr = [];
            for($i=0;$i<$diff_days+1;$i++){
                $date = $from->addDays($i);
                $label = (string)$date->format('D-d');
                $days_arr[$date->toDateString()]=[
                    'label'=>$label,
                    'count'=>0,
                ];
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $key = $field;
                    }else{
                        $key = $field['label'];
                    }
                    $days_arr[$date->toDateString()][$key] = 0;
                }
                $from->subDays($i);
            }

            foreach ($items as $item) {
                $created_date = Carbon::createFromTimestamp(strtotime($item->$date_field));
                $created = $created_date->toDateString();
                $days_arr[$created]['count']+=1;
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $days_arr[$created][$field]+=round($item->$field,$precision);
                    }else{
                        $days_arr[$created][$field['label']]+=self::checkQueryValidity($item,$field,$key);
                    }
                }
            }
            $unformatted_orders = $days_arr;


        }
        elseif($diff_days < 368){
            // show months
            $days_arr = [];
            for($i=0;$i<$diff_days+1;$i++){
                $date = $from->addDays($i);
                $label = (string)$date->format('M');
                $days_arr[$date->month]=[
                    'label'=>$label,
                    'count'=>0,
                ];
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $key = $field;
                    }else{
                        $key = $field['label'];
                    }
                    $days_arr[$date->month][$key] = 0;
                }
                $from->subDays($i);
            }

            foreach ($items as $item) {
                $created_date = Carbon::createFromTimestamp(strtotime($item->$date_field));
                $created = $created_date->month;
                $days_arr[$created]['count']+=1;
                foreach ($fields as $key=>$field){
                    if(!is_array($field)){
                        $days_arr[$created][$field]+=round($item->$field,$precision);
                    }else{
                        $days_arr[$created][$field['label']]+=self::checkQueryValidity($item,$field,$key);
                    }
                }
            }
            $unformatted_orders = $days_arr;

        }
        $drawable = [];
        foreach ($unformatted_orders as $item){
            $drawable['labels'][] = $item['label'];
            $drawable['count'][] = $item['count'];

            foreach ($fields as $key=>$field){
                if(!is_array($field)){
                    $key = $field;
                }else{
                    $key = $field['label'];
                }
                $drawable[$key][] = $item[$key];
            }
        }
        $return_data  = [];
        $labels = $drawable['labels'];
        $return_data['count'] = $drawable['count'];
        foreach ($fields as $key=>$field){
            if(!is_array($field)){
                $key = $field;
            }else{
                $key = $field['label'];
            }
            $return_data[$key] = $drawable[$key];
        }
        $fields[] = 'count';
        return [
            'labels'=>$labels,
            'graph_data'=>$return_data,
            'fields'=>$fields
        ];
    }

    public static function checkQueryValidity($item,$fields,$key){
        if($fields['operator'] == '==') {
            $query = $fields['field'];
            if (strtolower($item->$query) == strtolower($fields['value'])) {
                return $item->$key;
            }
        }
        if($fields['operator'] == 'in') {
            $query = $fields['field'];
            if (in_array($item->$query,$fields['value'])) {
                return $item->$key;
            }
        }
        return 0;
    }

    public static function getRangeFromGraph($period, $from, $to){
        $startRange = Carbon::createFromFormat('m-d-Y', $from)->startOfDay();
        $exploded = explode('-', $period);
        if (count($exploded) == 2) {
            $explodedDay = explode(' ', $exploded[1]);
            if (count($explodedDay) > 2) {
                $formatted = $startRange->format('Y-M-') . rtrim($exploded[0], ' ') . '-' . $explodedDay[2] . '-' . $explodedDay[3];
                $start = Carbon::createFromFormat('Y-M-d-h-A', $formatted)->startOfHour();
                $end = Carbon::createFromFormat('Y-M-d-h-A', $formatted)->endOfHour();
            } else {
                $day = $exploded[1];
                if($day < $startRange->day) {
                    //use to
                    $startRange = Carbon::createFromFormat('m-d-Y', $to)->startOfDay();
                }
                $start = Carbon::create($startRange->year, $startRange->month, $day)->startOfDay();
                $end = Carbon::create($startRange->year, $startRange->month, $day)->endOfDay();
            }
        } else {
            $start = Carbon::createFromFormat('Y-M', $startRange->year . '-' . $period)->startOfMonth();
            $end = Carbon::createFromFormat('Y-M', $startRange->year . '-' . $period)->endOfMonth();
        }
        return (object)[
            'start'=>$start,
            'end'=>$end
        ];
    }
    public static function getStockData($items, $start, $end, $dateField,$fields){
        $stockData = [];
        $addAmt = 0;
        $addtype = 'mins';
        $diffInDays = $start->diffInDays($end);
        if($diffInDays<8){
            $addtype = 'mins';
            $addAmt = 30;
        } elseif($diffInDays<32){
            $addAmt = 2;
            $addtype = 'hrs';
        }
        elseif($diffInDays<96){
            $addAmt = 6;
            $addtype = 'hrs';
        }
        elseif($diffInDays<364){
            $addAmt = 12;
            $addtype = 'hrs';
        } else {
            $addtype = 'hrs';
            $addAmt = 24;
        }
        while($start<$end){
            //
            $count = 0;
            $realStart = Carbon::createFromTimestamp($start->timestamp);
            if($addtype == 'mins') {
                $start->addMinutes($addAmt);
            } else {
                $start = $start->addHours($addAmt);
            }
            foreach ($items as $key=>$item){
                $created_date = Carbon::createFromTimestamp(strtotime($item->$dateField));
                if($created_date->between($realStart,$start)){
                    $count++;
                    unset($items[$key]);
                }
                if($created_date > $start) {
                    break;
                }
            }
            $data = [
                $start->timestamp * 1000,
                $count
            ];
            $stockData[] = $data;
        }
        return $stockData;
    }
}
