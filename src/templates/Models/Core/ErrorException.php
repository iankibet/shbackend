<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorException extends Model
{

	use HasFactory;
	protected $fillable = ["user_id","error","status","file","line","url"];

}
