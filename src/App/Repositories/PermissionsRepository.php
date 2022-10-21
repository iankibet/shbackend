<?php

namespace Iankibet\Shbackend\App\Repositories;

use Illuminate\Support\Facades\Storage;

class PermissionsRepository
{
    protected $filesPath,$user,$role,$permissions=[],$cache_name;
    public function __construct()
    {
        $this->filesPath = 'permissions/modules';
        $this->user = request()->user();
        if($this->user){
            $this->role = $this->user->role;
            $this->cache_name = 'permissions/'.$this->role.'_cache.json';
            if(!Storage::exists($this->cache_name)){
                $this->backupPermisions();
            }
            if(app()->environment() == 'local'){
                $this->backupPermisions();
            }
        }
    }

    public function getAllowedUrls($permissions=null){
        $modules = json_decode(Storage::get($this->cache_name));
        $allUrls = [];
        foreach ($modules as $permission=>$urls){
            if($permissions || $this->role == 'admin') {
                $permissions[] = 'common';
                if(in_array($permission,$permissions)){
                    $allUrls = array_merge($allUrls,$urls);
                }
            } else {
                $allUrls = array_merge($allUrls,$urls);
            }
        }
        return $allUrls;
    }

    public function backupPermisions($role = null){
        if($role){
            $this->role = $role;
            $this->cache_name = 'permissions/'.$this->role.'_cache.json';
        }
        $files = Storage::files($this->filesPath);
        $permissions = [];
        foreach ($files as $file){
            $arr = explode('/',$file);
            $module = str_replace('.json','',$arr[count($arr) - 1]);
            $hasChildren = true;
            $main = null;
            $moduleData = json_decode(Storage::get($file));
            $main = $moduleData->main;
            if(isset($moduleData->roles) && in_array($this->role,$moduleData->roles)) {
                $res = $this->getModuleUrls($moduleData,$main);
                $urls = $res['urls'];
                $children = $res['children'];
                $this->permissions[$module] = $urls;
                if($children){
                    if(!$main){
                        $main = trim($moduleData->main,'/');
                    }
                    $this->workOnchildren($children,$main,$module);
                }
            }
        }
        Storage::put($this->cache_name, json_encode($this->permissions));
    }

    protected function workOnchildren($children,$main,$module){
        foreach ($children as $slug=>$child){
            if(isset($child->roles) && in_array($this->role,$child->roles)) {
                $slug = $module.'.'.$slug;
//                if($slug == 'orders.orders.get_self_order'){
//                    dd($realMain,$main,$child,$res);
//                }
                $realMain = $child->main;
                $realMain2 = $child->main;
                if(!str_starts_with($realMain,'/') && $main){
                    $realMain = trim($main,'/').'/'.$child->main;
                }
                $res = $this->getModuleUrls($child,$realMain);
                $this->permissions[$slug] = $res['urls'];
//                if($slug == 'orders.orders.list_self_orders'){
//                    dd($realMain,$realMain2,$main,$child,$res);
//                }
                $children = $res['children'];
                if($children){
                    $this->workOnchildren($children,$realMain,$slug);
                }
            }
        }
    }

    protected function getModuleUrls($module, $parentMain){
        $mainUrl = rtrim($module->main,'/');
        if($parentMain && $mainUrl){
            if(!str_starts_with($mainUrl,'/')) {
                $mainUrl = trim($parentMain,'/').'/'.$mainUrl;
            }
        }
        $mainUrl = trim($mainUrl,'/');
        $roles = $module->roles;
        $children = false;
        if(isset($module->children)){
            $children = $module->children;
        }
        $childUrls = [];
        if($module->main){
            $childUrls[] = $parentMain;
        }
//        if($parentMain == 'config/settings/deadlines'){
//            dd($mainUrl, $module);
//        }
        if(isset($module->urls)){
            foreach ($module->urls as $url){
                $main = $url;
                if(str_starts_with($url,'/')) {
                        $url = ltrim($url,'/');
                } else {
                    $url = $parentMain.'/'.$url;
                }
//                $url = rtrim($url,'/');
                if($url){
                    $childUrls[] = trim($url,'/');
                }
            }
        }
        return [
          'urls'=>$childUrls,
          'children'=>$children
        ];
    }
}
