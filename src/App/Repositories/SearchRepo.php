<?php

namespace Iankibet\Shbackend\App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Str;
use function redirect;
use function request;
use function response;
use function storage_path;

class SearchRepo
{
    protected static $data;
    protected static $request_data;
    protected static $instance;
    protected static $tmp_key;
    protected static $tmp_value;
    public static function of($model,$base_tbl=null,$search_keys=null,$cache=false){
        self::$instance = new self();
        $request_data = request()->all();
        if(!$base_tbl){
            $base_tbl = $model->getModel()->getTable();
        }
        if(!$search_keys){
            $search_keys = [...$model->getModel()->getFillable(),'created_at','updated_at'];
        }
        $request_data['keys'] = $search_keys;
        $request_data['base_table'] = $base_tbl;
        self::$request_data = $request_data;
        if(isset($request_data['period'])){
            $start_date = Carbon::parse($request_data['from'])->startOfDay();
            $end_date = Carbon::parse($request_data['to'])->endOfDay();
            $model = $model->whereBetween($base_tbl.'.created_at',[$start_date,$end_date]);
        }
        if(isset($request_data['filter_value'])){
            $value = $request_data['filter_value'];

            $model = $model->where(function($query) use ($request_data,$value){
                $index = 0;
                foreach($request_data['keys'] as $key){
                    if(!strpos($key,'.') && $request_data['base_table'] != null)
                        $key = $request_data['base_table'].'.'.$key;
                    if(isset($request_data['exact']) && ($request_data['exact'] == 'true' || $request_data['exact'] === true)){
                        if($index == 0){
                            $query->where([
                                [$key,'like',$value]
                            ]);
                        }else{
                            $query->orWhere([
                                [$key,'like',$value]
                            ]);
                        }
                    } else {
                        if($index == 0){
                            $query->where([
                                [$key,'like','%'.$value.'%']
                            ]);
                        }else{
                            $query->orWhere([
                                [$key,'like','%'.$value.'%']
                            ]);
                        }
                    }

                    $index++;
                }

            });
        }
        $request_data = self::$request_data;
        if(isset($request_data['order_by']) && isset($request_data['order_method'])){
            $model = $model->orderBy($request_data['order_by'],$request_data['order_method']);
        }else{
            $model = $model->orderBy($base_tbl.'.id','desc');
        }

        if(isset($request_data['all'])){
            $data = $model->get();
        }else{
            if(!isset($request_data['download_csv'])){
                if(isset($request_data['per_page'])){
                    $data =  $model->paginate(round($request_data['per_page'],0));
                }else{
                    $data= $model->paginate(10);
                }
            }
        }
        self::$data = $data;
        return self::$instance;
    }

    public static function make($formatResponse = true){
        $data = self::$data;
        $request_data = self::$request_data;
        if(isset($request_data['all'])){
            return $data;
        }
        unset($request_data['page']);
        $data->appends($request_data);
        if(isset($request_data['download_csv'])){
            $csv_data = $data['data'];
            if(count($csv_data)){
                $single = $csv_data[0];
                unset($single['action']);
                $keys = array_keys($single);
                $file_path = storage_path("app/tmp/download_".time().Str::random(5).'.csv');
                $tmp = fopen($file_path,'w');
                fputcsv($tmp,$keys);
                foreach ($csv_data as $row){
                    unset($row['action']);
                    fputcsv($tmp,array_values($row));
                }
                fclose($tmp);
                $name = null;
                if($request_data['base_table']){
                    $name = $request_data['base_table'].date('_Y-m-d_h_i_a').'.csv';
                }
                return response()->download($file_path,$name)->deleteFileAfterSend();

            }else{
                return redirect()->back()->with('notice',['type'=>'error','message'=>'No records found']);
            }
        }
        return $data;

    }

    public static function response(){
        $data = self::$data;
        $request_data = self::$request_data;
        if(isset($request_data['all'])){
            return $data;
        }
        unset($request_data['page']);
        $data->appends($request_data);
//        if($pagination){
//            $pagination = $data->links()->__toString();
//            $data = $data->toArray();
//            $data['pagination'] = $pagination;
//        }
        if(isset($request_data['download_csv'])){
            $csv_data = $data['data'];
            if(count($csv_data)){
                $single = $csv_data[0];
                unset($single['action']);
                $keys = array_keys($single);
                $file_path = storage_path("app/tmp/download_".time().Str::random(5).'.csv');
                $tmp = fopen($file_path,'w');
                fputcsv($tmp,$keys);
                foreach ($csv_data as $row){
                    unset($row['action']);
                    fputcsv($tmp,array_values($row));
                }
                fclose($tmp);
                $name = null;
                if($request_data['base_table']){
                    $name = $request_data['base_table'].date('_Y-m-d_h_i_a').'.csv';
                }
                return response()->download($file_path,$name)->deleteFileAfterSend();

            }else{
                return redirect()->back()->with('notice',['type'=>'error','message'=>'No records found']);
            }
        }
        $response = [
            'status'=>'success',
            'data'=>$data
        ];
        return response($response,200);
    }
    public static function addColumn($column,$function){
        $records = self::$data;
        foreach($records as $index=>$record){
            $record->$column = $function($record);
            $records[$index] = $record;
        }
        self::$data = $records;
        return self::$instance;
    }
}
