<?php

namespace Iankibet\Shbackend\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CachingRepository
{
    protected $sh_cache_key = 'period_cache_keys';

    public function __construct()
    {

    }
    public function getCachedQueryResults($query,$period=null,$graph=null,...$graphParams,){
        $table = $query->getModel()->getTable();
        $originalBindings = $query->getBindings();;
        $period = request('period',$period);
        if($period == 'custom'){
            $from = Carbon::parse(request('from'));
            $to = Carbon::parse(request('to'));
            return $query->whereBetween($table.'.created_at',[$from,$to]);
        }
        $connection = $query->getConnection()->getName();
        $keyString = $connection.'!'.$table.'!'.$query->toSql().'!'.implode('~',$originalBindings);
        if($graphParams){
            $paramsStr = '';
            foreach ($graphParams as $graphParam){
                if(is_array($graphParam)){
                    $paramsStr.=count($graphParam);
                } else {
                    try{
                        $paramsStr.=$graphParam;
                    }catch (\Exception){
                    }
                }
            }
            $keyString.='!'.$paramsStr.'!'.$graph;
        }
        $cacheKey = base64_encode($keyString);
        return $this->getCacheResults($cacheKey,$period,$graphParams);
    }
    public  function getCacheResults($key,$period,$graphParams=null,$cache=false){
        $cache_key = $key.'_'.$period;
        $results = Cache::get($cache_key);
        if(!$results || $cache){
            $this->setCacheKey($key,$graphParams);
            $arr = explode('!',base64_decode($key));
            $sql = $arr[2];
            $table = $arr[1];
            $connection = $arr[0];
            $joiner = "where";
            if(strpos($sql,"where")){
                $joiner = "and";
            }
            $querySql ="$sql $joiner  `$table`.`created_at` between ? and ?";
            $periods = $this->getCachePeriods();
            if(!isset($periods[$period])){
                throw new \Exception("$period not found in allowed periods ".json_encode($periods));
            }
            $range = $periods[$period];
            if($arr[3]){
                $bindings = array_merge(explode('~',$arr[3]),$range);
            } else {
                $bindings = $range;
            }
            $results = DB::connection($connection)->select($querySql,$bindings);
            if($graphParams){
                $graphType = $arr[count($arr)-1];
                $results = $graphType == 'stock' ? GraphStatsRepository::getStockData($results,...$graphParams):GraphStatsRepository::getChartData($results,...$graphParams);
            }
            Cache::put($cache_key,$results);
        }
        return $results;
    }

    public function getCachePeriods(){
        $periods = [
            'today'=>[now()->startOfDay(),now()->endOfDay()],
            'yesterday'=>[now()->subDays(1)->startOfDay(),now()->subDay(1)->endOfDay()],
            '7_days'=>[now()->subDays(7)->startOfDay(),now()->endOfDay()],
            'this_week'=>[now()->startOfWeek(),now()->endOfWeek()],
            'this_month'=>[now()->startOfMonth(),now()->endOfMonth()],
            'last_month'=>[now()->subMonth()->startOfMonth(),now()->subMonth()->endOfMonth()],
            '1_month'=>[now()->subDays(30)->startOfDay(),now()->endOfDay()],
            '1_year'=>[now()->subYear(1)->startOfDay(),now()->endOfDay()],
            'this_year'=>[now()->startOfYear(),now()->endOfYear()],
        ];
        $start = 2018;
        while ($start<=date('Y')){
            $periods[$start] = [Carbon::create($start)->startOfYear(),Carbon::create($start)->endOfYear()];
            $start++;
        }
        $periods['all_time'] = [Carbon::create(2018), now()->endOfDay()];
        return $periods;
    }
    public function cacheKeyPeriods($key,$graphData = null){
        $keys = $this->getCacheKeys();
        $periods = array_keys($this->getCachePeriods());
        foreach ($periods as $period){
            if(!is_array($graphData)){
                $graphData = null;
            }
            $this->getCacheResults($key,$period,$graphData,true);
        }
        $this->setCacheKey($key,$graphData);
    }
    public function getCacheKeys(){
        return Cache::get($this->sh_cache_key,[]);
    }
    public function emptyCacheKeys(){
        $keys = $this->getCacheKeys();
        foreach ($keys as $key=>$lastUpdated){
            $periods = array_keys($this->getCachePeriods());
            foreach ($periods as $period){
                Cache::forget($key.'_'.$period);
            }
        }
        return Cache::forget($this->sh_cache_key);
    }

    protected function setCacheKey($cacheKey,$graphParams){
        $cacheKeys = Cache::get($this->sh_cache_key,[]);
        $cacheKeys[$cacheKey] = [
            'last_used'=>now()->toDateTimeString(),
            'graphData'=>$graphParams
        ];
        Cache::put($this->sh_cache_key,$cacheKeys);
        return true;
    }
}
