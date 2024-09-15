<?php

namespace Iankibet\Shbackend\App\Traits;

use App\Models\Core\DepartmentPermission;
use Iankibet\Shbackend\App\Repositories\RoleRepository;

trait HasShPermission
{
    protected $shPermissions = [];

    /**
     * @param array $shPermissions
     */
    protected function setShPermissions($slug = null): void
    {
        if($this->role != 'admin'){
            $this->shPermissions[$slug] = RoleRepository::getRolePermissions($this->role);
        } else {
            $arr = explode('.',$slug);
            $module = $arr[0];
            $modulePermissions = DepartmentPermission::query()->where('department_id',$this->department_id)->where('module',$module)->first();
            if($modulePermissions){
                $permissions = json_decode($modulePermissions->permissions);
                $permissions = collect($permissions)->map(function($permission) use ($module) {
                    return $module . '.' . $permission;
                });
                $permissions = $permissions->toArray();
                $permissions[] = $module;
                $this->shPermissions[$slug] = $permissions;
            }
        }
    }

    public function isAllowedTo($slug): bool
    {
        if(!isset($this->shPermissions[$slug])) {
            $this->setShPermissions($slug);
        }
        $permissions = $this->shPermissions[$slug] ?? [];
        return in_array($slug, $permissions);
    }
}
