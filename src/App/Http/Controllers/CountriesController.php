<?php

namespace Iankibet\Shbackend\App\Http\Controllers;

use App\Http\Controllers\Controller;

class CountriesController extends Controller
{
    //

    public function getCountryCode(){
        $fields = request('fields','countryCode');
        $fields = explode(',', $fields);
        if(!is_array($fields) || !count($fields)){
            $fields = ['country','countryCode','region','regionName','city','zip','lat','lon','timezone','isp','org','as'];
        }
        $ip = $this->get_client_ip_env();
        $contents = file_get_contents("http://ip-api.com/json/$ip");
        $resp = json_decode($contents,true);
        $data = [];
        if(is_array($fields) && count($fields)) {
            foreach ($fields as $field) {
                if (isset($resp[$field])) {
                    $data[$field] = $resp[$field];
                }
            }
        }
        return $data;
    }

    public function get_client_ip_env() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        if($ipaddress == '127.0.0.1'){
            $ipaddress = '105.161.230.38';
//            $ipaddress = '72.229.28.185';
        }
        return explode(',',$ipaddress)[0];
    }
}
