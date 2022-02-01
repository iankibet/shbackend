<?php
namespace App\Repositories;


class GraphStatsRepository
{
    public static function getDrawables($from,$to,$query,$fields=[],$precision=2,$table=''){
        $diff_days = $from->diffInDays($to);
        $items = $query->where([
            [$table.'created_at','>=',$from->startOfDay()],
            [$table.'created_at','<=',$to->endOfDay()],
        ])->get();
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
                $created = $item->created_at->hour;
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
                $created = $item->created_at->toDateString();
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
                $created = $item->created_at->toDateString();
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


        }elseif($diff_days < 368){
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
                $created = $item->created_at->month;
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
}
