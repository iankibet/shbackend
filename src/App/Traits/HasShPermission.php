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
        if (!RoleRepository::isDepartmentScopedUser($this)) {
            $this->shPermissions[$slug] = RoleRepository::getRolePermissions($this->role);
        } else {
            $arr = explode('.', $slug);
            $module = $arr[0];
            $this->shPermissions[$slug] = RoleRepository::getDepartmentModulePermissions($this->department_id, $module);
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
