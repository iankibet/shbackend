<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentPermission extends Model
{

	use HasFactory;
	protected $fillable = ["module","permissions","urls","department_id"];

}
