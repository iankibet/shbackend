<?php

namespace Iankibet\Shbackend\App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PermissionsRepository
{
    protected $filesPath,$user,$role,$permissions=[],$cache_name;
    public function __construct()
    {
        $this->filesPath = 'permissions/modules';
        $this->user = request()->user();
        $userRoles = [];
        if($this->user){
            $this->role = $this->user->role;
            if(isset($this->user->roles)){
                foreach ($this->user->roles as $role){
                    $userRoles[] = $role->role;
                }
            }
            if(!count($userRoles)){
                $userRoles = [$this->role];
            }
            $this->userRoles = $userRoles;
            $this->cache_name = 'permissions/'.implode('_',$userRoles).'_cache.json';
        }
    }

    public function getAllowedUrls($permissions=null){
        if(!Cache::get($this->cache_name)){
            $this->backupPermisions();
        }
        if(app()->environment() == 'local'){
            $this->backupPermisions();
        }
        $modules = Cache::get($this->cache_name);
        $modules = json_decode(json_encode($modules));
        $allUrls = [];
        foreach ($modules as $permission=>$moduleData){
            $urls = $moduleData->urls;
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
    public function getAllowedQlQueries($permissions=null){
        if(request()->method() == 'POST') {
            return $this->getAllowedMutations($permissions);
        } else {
            $modules = Cache::get($this->cache_name);
            $modules = json_decode(json_encode($modules));
            $allQlQueries = [];
            foreach ($modules as $permission=>$moduleData){
                $qlQueries = $moduleData->qlQueries;
                if($permissions || $this->role == 'admin') {
                    $permissions[] = 'common';
                    if(in_array($permission,$permissions)){
                        $allQlQueries = array_merge($allQlQueries,$qlQueries);
                    }
                } else {
                    $allQlQueries = array_merge($allQlQueries,$qlQueries);
                }
            }
            return $allQlQueries;
        }
    }
    public function getAllowedMutations($permissions=null){
        $modules = Cache::get($this->cache_name);
        $modules = json_decode(json_encode($modules));
        $allQlMutations = [];
        foreach ($modules as $permission=>$moduleData){
            $qlMutations = $moduleData->qlMutations;
            if($permissions || $this->role == 'admin') {
                $permissions[] = 'common';
                if(in_array($permission,$permissions)){
                    $allQlMutations = array_merge($allQlMutations,$qlMutations);
                }
            } else {
                $allQlMutations = array_merge($allQlMutations,$qlMutations);
            }
        }
        return $allQlMutations;
    }
    protected $userRoles = [];
    public function backupPermisions($role = null){
        $isWriting = Cache::get('permissionsUpdated',false);
        if($isWriting)
            return;
        Cache::put('permissionsUpdated',now()->timestamp);
        $userRoles = [];
        if($this->user && isset($this->user->roles)){
            foreach ($this->user->roles as $role){
                $userRoles[] = $role->role;
            }
        }
        if(!count($userRoles)){
            $userRoles = [$this->role];
        }
        $this->userRoles = $userRoles;
//        try{
        if($role){
            $this->role = $role;
            $this->cache_name = 'permissions/'.implode('_',$userRoles).'_cache.json';
        }
        $files = Storage::files($this->filesPath);
        $permissions = [];
        foreach ($files as $file){
            $arr = explode('/',$file);
            $module = str_replace('.json','',$arr[count($arr) - 1]);
            $hasChildren = true;
            $main = null;
            $moduleData = json_decode(Storage::get($file));
            if(isset($moduleData->roles) && count(array_intersect($userRoles,$moduleData->roles))) {
                if(isset($moduleData->main)){
                    $main = $moduleData->main;
                    $res = $this->getModuleUrls($moduleData,$main);
                    $children = $res['children'];
                    $this->permissions[$module] = [
                        'urls'=>$res['urls'],
                        'qlQueries'=>$res['qlQueries'],
                        'qlMutations'=>$res['qlMutations']
                    ];
                    if($children){
                        if(!$main){
                            $main = trim($moduleData->main,'/');
                        }
                        $this->workOnchildren($children,$main,$module);
                    }
                }

            }
        }
        Cache::put($this->cache_name, $this->permissions);
        Cache::forget('permissionsUpdated');
//        }catch (\Exception $exception) {
//            Cache::forget('permissionsUpdated');
//            throw new \Exception($exception->getMessage());
//        }

    }

    protected function workOnchildren($children,$main,$module){
        foreach ($children as $slug=>$child){
            if(isset($child->roles) && count(array_intersect($this->userRoles,$child->roles))) {
                $slug = $module.'.'.$slug;
//                if($slug == 'orders.orders.get_self_order'){
//                    dd($realMain,$main,$child,$res);
//                }
                if(!isset($child->main)){
                    $child->main = '';
                }
                $realMain = $child->main;
                $realMain2 = $child->main;
                if(!str_starts_with($realMain,'/') && $main){
                    $realMain = trim($main,'/').'/'.$child->main;
                }
                $res = $this->getModuleUrls($child,$realMain);
                $this->permissions[$slug] = [
                    'urls'=>$res['urls'],
                    'qlQueries'=>$res['qlQueries'],
                    'qlMutations'=>$res['qlMutations'],
                ];
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
        $qlQueries = [];
        if(isset($module->qlQueries)){
            if(is_array($module->qlQueries)){
                $qlQueries = $module->qlQueries;
            } else {
                $qlQueries[] = $module->qlQueries;
            }
        }
        $qlMutations = [];
        if(isset($module->qlMutations)){
            if(is_array($module->qlMutations)){
                $qlMutations = $module->qlMutations;
            } else {
                $qlMutations[] = $module->qlMutations;
            }
        }
        return [
            'urls'=>$childUrls,
            'children'=>$children,
            'qlQueries'=>$qlQueries,
            'qlMutations'=>$qlMutations,
        ];
    }
}
