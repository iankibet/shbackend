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
        // Cloudflare passes the real visitor IP in CF-Connecting-IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))
            $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
        else if (!empty($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (!empty($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (!empty($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (!empty($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (!empty($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        if($ipaddress == '127.0.0.1'){
            $ipaddress = '105.161.230.38';
        }
        return trim(explode(',',$ipaddress)[0]);
    }
}
