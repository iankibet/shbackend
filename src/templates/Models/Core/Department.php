<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{

	use HasFactory;
	protected $fillable = ["name","description"];

    public function permissions(){
        return $this->hasMany(DepartmentPermission::class);
    }
}
