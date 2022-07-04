<?php
/**
 * Created by PhpStorm.
 * User: iankibet
 * Date: 2016/06/04
 * Time: 7:47 AM
 */

namespace Iankibet\Shbackend\Iankibet\Shbackend\App\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Iankibet\Shbackend\Repositories\UserGroup;

class RoleRepository
{
    protected $path;
    protected $user;
    protected $menus;
    protected $allow = false;
    protected $request;
    protected $is_app = 0;
    protected $common;
    protected $userPermissions;
    protected $allPermissionsFile;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->user = Auth::user();
        $this->path = Route::getFacadeRoot()->current()->uri();
        $sub_path = strtolower($this->path);
        $sub_path = str_replace('unpaid', 'bids', $sub_path);
        $sub_path = str_replace('disputes', 'resolution', $sub_path);
        $sub_path = str_replace('stud', 'Home', $sub_path);
        $sub_pages = explode('/', $sub_path);

        if (@$sub_pages[0] == 'member' && @$sub_pages['1'] == 'app') {
            $this->is_app = 1;
        }
        if(file_exists(storage_path('app/shara-framework/allPermissions.json'))){
            $this->allPermissionsFile = storage_path('app/shara-framework/allPermissions.json');
        } else {
            $this->allPermissionsFile = __DIR__.'/../../templates/allPermissions.json';
        }

    }

    public function check($allow = false)
    {
        $this->allow = $allow;
        $menus = (object)[];
        $this->menus = $menus;
        if (Auth::user()) {
            $user = Auth::user();
            $role = $this->user->role;
            if (!$role) {
                $this->user->role = "admin1";
                $this->user->update();
                $role = "admin";
            }
            if(isset($menus->$role)) {
                $allowed = $menus->$role;
            } else {
                $allowed = [];
            }
            if ($this->user->role == 'admin') {
                $permissions = [];
                if ($this->user->department)
                    $permissions = json_decode($this->user->department->permissions);
                $allowed = [];
                if ($permissions == NULL) {

                    $permissions = [];
                }
                foreach ($menus->admin as $mnu) {
                    if (in_array($mnu->slug, $permissions))
                        $allowed[] = $mnu;
                }
                $this->userPermissions = $permissions;
                $this->authorize($allowed);
            } else {
                $this->userPermissions = $allowed;
                $this->authorize($allowed);
            }
        }

    }

    protected function extractFromAllUrls()
    {
        $user = $this->user;
        $role = $user->role;
        $urls = [];
        if ($role == 'admin') {
            $allowed_permissions = [];
            if ($user->department) {
                $allowed_permissions = json_decode($user->department->permissions);
            }
        } else {
            $allowed_permissions = self::getRolePermissions($user->role);
        }
        $urls = [];
        if(file_exists(storage_path('app/shara-framework/allPermissions.json'))){
            $allPermissionsFile = storage_path('app/shara-framework/allPermissions.json');
        } else {
            $allPermissionsFile = __DIR__.'/../../templates/allPermissions.json';
        }
        $allPermissions = @json_decode(@file_get_contents($allPermissionsFile));
        foreach ($allPermissions as $slug => $allPermission) {
            $mainSlug = $slug;
            $roles = $allPermission->roles;
            if (in_array($role, $roles) && in_array($mainSlug, $allowed_permissions)) {
                $urls = array_merge($urls, $this->extractMainBlock($allPermission));
            }
            foreach ($allPermission->childPermissions as $childSlug => $childPermission) {
                $full_slug = $mainSlug . '.' . $childSlug;
                $mainUrl = $childPermission->main;
                if (substr($mainUrl, 0, 1) != '/') {
                    $mainUrl = trim($allPermission->main, '/') . '/' . $mainUrl;
                }
                $childPermission->main = $mainUrl;
                if (in_array($role, $childPermission->roles) && in_array($full_slug, $allowed_permissions)) {
                    $urls = array_merge($urls, $this->extractMainBlock($childPermission));
                }
            }
        }
        return $urls;
    }

    public static function getRolePermissions($role)
    {
        $menus = null;
        if(file_exists(storage_path('app/shara-framework/allPermissions.json'))){
            $allPermissionsFile = storage_path('app/shara-framework/allPermissions.json');
        } else {
            $allPermissionsFile = __DIR__.'/../../templates/allPermissions.json';
        }
        if(file_exists(storage_path('app/system/roles.json'))){
            $menus = json_decode(file_get_contents(storage_path('app/system/roles.json')));
        }
        if($menus) {
            $permissions = [];
            if(isset($menus->$role))
                $permissions = $menus->$role;
            $slugs = [];
            foreach ($permissions as $permission) {
                $mainSlug = $permission->slug;
                $slugs[] = $mainSlug;

                if (isset($permission->child_permissions)) {
                    foreach ($permission->child_permissions as $child_permission) {
                        $slug = $child_permission->slug;
                        $slugs[] = $mainSlug . '.' . $slug;
                    }
                }
            }
        }
        $allPermissions = json_decode(file_get_contents($allPermissionsFile));
        foreach ($allPermissions as $slug => $allPermission) {
            $mainSlug = $slug;
            $roles = $allPermission->roles;
            if (in_array($role, $roles)) {
                $slugs[] = $mainSlug;
            }
            foreach ($allPermission->childPermissions as $childSlug => $childPermission) {
                $full_slug = $mainSlug . '.' . $childSlug;
                if (in_array($role, $childPermission->roles)) {
                    $slugs[] = $full_slug;
                }
            }
        }
        return $slugs;
    }

    public static function extractRoleSlugs($permission, $role)
    {

    }

    protected function extractMainBlock($permission)
    {
        $mainUrl = $permission->main;
        $urls = [];
        if (isset($permission->urls)) {
            $permissionUrls = $permission->urls;
            $urls = $this->extractPermissionUrls($permissionUrls, $mainUrl);
        }
        $urls[] = $mainUrl;
        return $urls;
    }

    protected function extractPermissionUrls($paths, $main)
    {
        $urls = [];
        foreach ($paths as $path) {
            if (substr($path, 0, 1) != '/') {
                $urls[] = trim($main . '/' . $path, '/');
            } else {
                $urls[] = trim($path, '/');
            }
        }
        return $urls;
    }

    protected function authorize($backend)
    {
        $current = preg_replace('/\d/', '', $this->path);
        $current = preg_replace('/{(.*?)}/', '', $current);
        $current = rtrim($current, '/');
        $backend_urls = $this->separateLinks($backend);
        $roleUrls = $this->extractFromAllUrls();
        $backend_urls = array_merge($backend_urls, $roleUrls);
        $current = str_replace("//", "/", $current);
        $current = str_replace("//", "/", $current);
        $current = str_replace("//", "/", $current);
//        dd($this->path,$business_urls);
//        dd($current,$backend_urls);
        $backend_urls[] = '/';
        $backend_urls[] = '';
        $backend_urls[] = 'auth/user';
        if (strpos($current, 'api') !== false) {
            $current = substr_replace($current, '', 0, 4);
        }
        if (!in_array($current, $backend_urls)) {
            $this->unauthorized();
        }
    }

    public function filterBackend($backend)
    {
        $allowed = [];
        if ($this->user->role == 'business') {
            $group_permissions = $this->user->userGroup->permissions;

        } elseif ($this->user->role == 'super') {
            $group_permissions = json_decode($this->user->group->permissions);
        }
        if (!$group_permissions) {
            $group_permissions = [];
        }
        foreach ($backend as $single) {
            if (in_array($single->slug, $group_permissions)) {
                $allowed[] = $single;
                if ($single->slug == 'user_management') {
                    $user_groups = UserGroup::all(['id', 'name']);
                    foreach ($user_groups as $group) {
                        $menu = new \stdClass();
                        $menu->url = "users/view/" . $group->id;
                        $menu->label = $group->name;
                        $single->children[] = $menu;
                    }
                }
            }

        }
        return $allowed;
    }

    protected function separateLinks($raw_menu)
    {
        $links = [];
        foreach ($raw_menu as $single) {
            $main_url = "";
            if (isset($single->url)) {
                $child_url = preg_replace('/\d/', '', $single->url);
                $child_url = rtrim($child_url, '/');
                $main_url = $child_url;
                if (!in_array($child_url, $links))
                    $links[] = $child_url;
            } else if (isset($single->main_url)) {
                $child_url = preg_replace('/\d/', '', $single->main_url);
                $child_url = rtrim($child_url, '/');
                $main_url = $child_url;
                if (!in_array($child_url, $links))
                    $links[] = $child_url;
            } else if (isset($single->main)) {
                $child_url = preg_replace('/\d/', '', $single->main);
                $child_url = rtrim($child_url, '/');
                $main_url = $child_url;
                if (!in_array($child_url, $links))
                    $links[] = $child_url;
            }
            if (@$single->type == 'many') {
                foreach ($single->children as $child) {
                    $child_url = preg_replace('/\d/', '', $child->url);
                    $child_url = rtrim($child_url, '/');
                    if (!in_array($child_url, $links))
                        $links[] = $child_url;
                }
                if (isset($single->urls)) {
                    foreach ($single->urls as $url) {
                        $url = rtrim($url, '/');
                        $url = preg_replace('/\d/', '', $url);
                        if (!in_array($url, $links))
                            $links[] = $url;
                    }
                }

                if (isset($single->subs) && isset($single->main)) {
                    $child_url = preg_replace('/\d/', '', $single->main);
                    $child_url = rtrim($child_url, '/');
                    $main_url = $child_url;
                    foreach ($single->subs as $url) {
                        $url = rtrim($url, '/');
                        $url = preg_replace('/\d/', '', $url);
                        $url = $main_url . '/' . $url;
                        if (!in_array($url, $links))
                            $links[] = $url;
                    }
                }
            } else {
                if (isset($single->menus->url)) {
                    $child_url = preg_replace('/\d/', '', $single->menus->url);
                    $child_url = rtrim($child_url, '/');
                    $main_url = $child_url;
                    if (!in_array($child_url, $links))
                        $links[] = $child_url;
                }
                if (isset($single->subs)) {
                    foreach ($single->subs as $url) {
                        $url = rtrim($url, '/');
                        $url = preg_replace('/\d/', '', $url);
                        $url = $main_url . '/' . $url;
                        if (!in_array($url, $links))
                            $links[] = $url;
                    }
                }
            }
            if (isset($single->urls))
                foreach ($single->urls as $url) {
                    $url = rtrim($url, '/');
                    $url = preg_replace('/\d/', '', $url);
                    if (!in_array($url, $links))
                        $links[] = $url;
                }
            if (isset($single->child_permissions)) {
                foreach ($single->child_permissions as $child_permission) {
                    if (in_array($single->slug . '.' . $child_permission->slug, $this->userPermissions)) {
                        $url = $child_permission->url;
                        if (substr($url, 0, 1) !== '/') {
                            //main url
                            $url = $main_url . '/' . $url;
                        }
                        $url = rtrim($url, '/');
                        $url = preg_replace('/\d/', '', $url);
                        $url = ltrim($url, '/');
                        if (!in_array($url, $links))
                            $links[] = $url;
                    }
                }
            }
        }
        return $links;
    }

    protected function sanitizeBusinessUrls($urls)
    {

    }

    public function unauthorized()
    {
        $common_paths = ['logout', 'login', 'register'];
        $path = $this->path;
        if (!in_array($path, $common_paths)) {
            App::abort(403, "Not authorized to access this page/resource/endpoint");
            die('You are not authorized to perform this action');
        }
    }
}
