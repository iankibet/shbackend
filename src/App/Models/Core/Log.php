<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['alug','log','user_id','slug','model_id','model','device'];
}
