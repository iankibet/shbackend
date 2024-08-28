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
            $this->shPermissions = RoleRepository::getRolePermissions($this->role);
        } else {
            $arr = explode('.',$slug);
            $module = $arr[0];
            $slug = array_pop($arr);
            $modulePermissions = DepartmentPermission::query()->where('department_id',$this->department_id)->where('module',$module)->first();
            if($modulePermissions){
                $this->shPermissions = json_decode($modulePermissions->permissions);
            }
        }
    }

    public function isAllowed($permission): bool
    {
        if(!$this->shPermissions) {
            $this->setShPermissions();
        }
        return in_array($permission, $this->shPermissions);
    }
}
